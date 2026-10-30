<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function makeElection(array $overrides = []): Election
    {
        return Election::create(array_merge([
            'name' => 'Pemilihan Contoh',
            'year' => '2026',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_voter_access_when_results_are_not_public(): void
    {
        $election = $this->makeElection();
        $election->setting()->create(['status' => 'final', 'results_public' => false]);

        $this->get('/')->assertRedirect('/voter/login');
    }

    public function test_guest_sees_public_results_page_when_published(): void
    {
        $election = $this->makeElection();
        $election->setting()->create(['status' => 'final', 'results_public' => true]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PublicResults')
                ->where('summary.total_votes', 0)
                ->has('results', 0));
    }

    public function test_superadmin_can_publish_results(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $election = $this->makeElection();
        $election->setting()->create(['status' => 'final']);

        $this->actingAs($user)->put('/settings', [
            'status' => 'closed',
            'voting_enabled' => false,
            'results_public' => true,
        ])->assertRedirect();

        $this->assertTrue($election->setting()->first()->results_public);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/voters')->assertRedirect('/login');
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_superadmin_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/')->assertOk();
    }

    public function test_admin_can_access_only_permitted_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        foreach (['/', '/voters', '/candidates', '/profile'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        foreach (['/school-profile', '/elections', '/settings', '/results', '/users'] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }

        $this->actingAs($admin)->put('/settings', ['status' => 'ongoing', 'voting_enabled' => true])->assertForbidden();
        $this->actingAs($admin)->post('/elections', [])->assertForbidden();
    }

    public function test_admin_can_modify_data_only_when_not_started_or_paused(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $election = $this->makeElection();
        $setting = $election->setting()->create(['status' => 'not_started', 'voting_enabled' => false]);

        $payload = ['election_id' => $election->id, 'name' => 'Budi', 'nisn' => '1234567890', 'grade' => 'X', 'is_active' => true];

        foreach (['not_started', 'paused'] as $status) {
            $setting->update(['status' => $status]);
            $this->actingAs($admin)->post('/voters', [...$payload, 'nisn' => $status === 'paused' ? '1234567891' : '1234567890'])
                ->assertSessionMissing('error');
        }

        $this->assertSame(2, $election->voters()->count());

        foreach (['ongoing', 'closed', 'final'] as $status) {
            $setting->update(['status' => $status]);
            $this->actingAs($admin)->post('/voters', [...$payload, 'nisn' => '9999999999'])
                ->assertSessionHas('error');
        }

        $this->assertSame(2, $election->voters()->count());
    }

    public function test_admin_can_view_results_only_when_final(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $election = $this->makeElection();
        $setting = $election->setting()->create(['status' => 'ongoing', 'voting_enabled' => true]);

        $this->actingAs($admin)->get('/results')->assertForbidden();

        $setting->update(['status' => 'final']);
        $this->actingAs($admin)->get('/results')->assertOk();
    }

    public function test_logout_forces_full_page_load_of_login(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post('/logout')
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('login'));

        $this->assertGuest();
    }

    public function test_superadmin_can_create_update_and_delete_voter(): void
    {
        $election = $this->makeElection();

        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/voters', [
            'election_id' => $election->id,
            'nisn' => '0012345678',
            'name' => 'Pemilih Contoh',
            'grade' => 'XII IPA 1',
            'is_active' => true,
        ])->assertRedirect();

        $voter = Voter::where('nisn', '0012345678')->firstOrFail();

        $this->assertSame($election->id, $voter->election_id);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $voter->id);

        $this->actingAs($user)->put("/voters/{$voter->id}", [
            'nisn' => '0012345678',
            'name' => 'Pemilih Diubah',
            'grade' => 'XII IPA 2',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('voters', [
            'id' => $voter->id,
            'name' => 'Pemilih Diubah',
            'grade' => 'XII IPA 2',
        ]);

        $this->actingAs($user)->delete("/voters/{$voter->id}")->assertRedirect();

        $this->assertDatabaseMissing('voters', ['id' => $voter->id]);
    }

    public function test_superadmin_can_create_candidate_with_photo(): void
    {
        Storage::fake('public');

        $election = $this->makeElection();

        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/candidates', [
            'order_number' => 1,
            'chairman_name' => 'Ketua Contoh',
            'vice_chairman_name' => 'Wakil Contoh',
            'vision' => 'Visi contoh',
            'mission' => 'Misi contoh',
            'photo' => UploadedFile::fake()->image('ketua.jpg'),
        ])->assertRedirect();

        $candidate = Candidate::where('order_number', 1)->firstOrFail();

        $this->assertSame($election->id, $candidate->election_id);
        $this->assertNotNull($candidate->photo_path);
        Storage::disk('public')->assertExists($candidate->photo_path);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($candidate->photo_path));
        $this->assertSame([1200, 900], [$width, $height]);
    }

    public function test_candidate_vision_and_mission_are_sanitized(): void
    {
        $this->makeElection();
        $user = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $this->actingAs($user)->post('/candidates', [
            'order_number' => 1,
            'chairman_name' => 'Ketua',
            'vision' => '<p onclick="x()">Visi <b>baik</b></p><script>alert(1)</script>',
            'mission' => '<p><br></p>',
        ])->assertSessionHasNoErrors();

        $candidate = Candidate::where('order_number', 1)->firstOrFail();

        $this->assertSame('<p>Visi <b>baik</b></p>alert(1)', $candidate->vision);
        $this->assertNull($candidate->mission);
    }

    public function test_school_profile_validation_uses_indonesian_messages(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put('/school-profile', [])
            ->assertSessionHasErrors(['name', 'address', 'headmaster_name']);

        $errors = session('errors');

        $this->assertSame('Kolom nama sekolah wajib diisi.', $errors->first('name'));
        $this->assertSame('Kolom alamat wajib diisi.', $errors->first('address'));
        $this->assertSame('Kolom nama kepala sekolah wajib diisi.', $errors->first('headmaster_name'));
    }

    public function test_school_headmaster_nip_rejects_non_numeric_input(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Sekolah Contoh',
            'address' => 'Jl. Contoh No. 1',
            'headmaster_name' => 'Kepala Contoh',
        ];

        foreach (['abc123', '123.45', '12e3', '<script>alert(1)</script>', "123\n456"] as $invalidNip) {
            $this->actingAs($user)->put('/school-profile', $payload + [
                'headmaster_nip' => $invalidNip,
            ])->assertSessionHasErrors('headmaster_nip');
        }

        $this->actingAs($user)->put('/school-profile', $payload + [
            'headmaster_nip' => '123456789012345678',
        ])->assertSessionHasNoErrors();

        $this->assertSame('123456789012345678', SchoolProfile::firstOrFail()->headmaster_nip);
    }

    public function test_school_logo_is_stored_with_a_random_filename(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put('/school-profile', [
            'name' => 'Sekolah Contoh',
            'address' => 'Jl. Contoh No. 1',
            'headmaster_name' => 'Kepala Contoh',
            'headmaster_nip' => '123456789',
            'logo' => UploadedFile::fake()->image('logo-asli.png'),
        ])->assertRedirect();

        $profile = SchoolProfile::firstOrFail();

        $this->assertNotNull($profile->logo_path);
        $this->assertStringNotContainsString('logo-asli', $profile->logo_path);
        $this->assertMatchesRegularExpression('/^school\/[A-Za-z0-9]{40}\.png$/', $profile->logo_path);
        Storage::disk('public')->assertExists($profile->logo_path);
    }

    public function test_superadmin_can_manage_elections_and_activate_one_period(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/elections', [
            'name' => 'Pemilihan Ketua OSIS',
            'year' => '2026',
            'starts_at' => '2026-11-01 08:00:00',
            'ends_at' => '2026-11-01 12:00:00',
        ])->assertRedirect();

        $first = Election::firstOrFail();
        $this->assertTrue($first->is_active);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $first->id);

        $second = $this->makeElection([
            'name' => 'Pemilihan Ketua OSIS 2027',
            'year' => '2027',
            'is_active' => false,
        ]);

        $this->actingAs($user)->put("/elections/{$first->id}", [
            'name' => 'Pemilihan Ketua OSIS 2026',
            'year' => '2026',
            'starts_at' => '2026-11-02 08:00:00',
            'ends_at' => '2026-11-02 12:00:00',
        ])->assertRedirect();

        $this->assertSame('Pemilihan Ketua OSIS 2026', $first->fresh()->name);

        $this->actingAs($user)->post("/elections/{$second->id}/activate")->assertRedirect();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);

        $this->actingAs($user)->delete("/elections/{$second->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('elections', ['id' => $second->id]);

        $this->actingAs($user)->post("/elections/{$second->id}/deactivate")->assertRedirect();
        $this->assertFalse($second->fresh()->is_active);
        $this->assertSame(0, Election::where('is_active', true)->count());
    }

    public function test_deleting_an_inactive_election_removes_related_data(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);
        $election = $this->makeElection(['is_active' => false]);

        $voter = Voter::create([
            'election_id' => $election->id,
            'nisn' => '1000000001',
            'name' => 'Siswa Satu',
            'grade' => 'X',
            'is_active' => true,
        ]);

        $this->actingAs($user)->delete("/elections/{$election->id}")->assertSessionHas('success');

        $this->assertDatabaseMissing('elections', ['id' => $election->id]);
        $this->assertDatabaseMissing('voters', ['id' => $voter->id]);
    }

    public function test_elections_index_supports_search_and_pagination(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        foreach (range(1, 12) as $number) {
            $this->makeElection(['name' => "Periode {$number}", 'year' => (string) (2000 + $number), 'is_active' => false]);
        }

        $this->actingAs($user)->get('/elections')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('elections.data', 10)->where('elections.total', 12));

        $this->actingAs($user)->get('/elections?search=Periode 12')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('elections.data', 1));
    }

    public function test_election_validation_uses_indonesian_messages(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/elections', [])
            ->assertSessionHasErrors(['name', 'year', 'starts_at', 'ends_at']);

        $errors = session('errors');

        $this->assertSame('Kolom nama pemilihan wajib diisi.', $errors->first('name'));
        $this->assertSame('Kolom tahun wajib diisi.', $errors->first('year'));
        $this->assertSame('Kolom waktu mulai wajib diisi.', $errors->first('starts_at'));
        $this->assertSame('Kolom waktu selesai wajib diisi.', $errors->first('ends_at'));
    }

    public function test_election_end_time_must_be_after_start_time(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/elections', [
            'name' => 'Pemilihan Contoh',
            'year' => '2026',
            'starts_at' => '2026-11-01 12:00:00',
            'ends_at' => '2026-11-01 08:00:00',
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_voters_and_candidates_are_scoped_to_the_active_election(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $old = $this->makeElection(['name' => 'Periode Lama', 'year' => '2025', 'is_active' => false]);
        $current = $this->makeElection(['name' => 'Periode Baru', 'year' => '2026', 'is_active' => true]);

        Voter::create([
            'election_id' => $old->id,
            'nisn' => '1234567890',
            'name' => 'Pemilih Lama',
            'grade' => 'XII',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/voters')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Voters/Index')
                ->where('election.id', $current->id)
                ->has('voters.data', 0));

        $this->actingAs($user)->post('/voters', [
            'election_id' => $current->id,
            'nisn' => '1234567890',
            'name' => 'Pemilih Baru',
            'grade' => 'XII',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('voters', [
            'election_id' => $current->id,
            'nisn' => '1234567890',
        ]);

        $this->actingAs($user)->get("/voters?election={$old->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('election.id', $old->id)
                ->has('elections', 2)
                ->has('voters.data', 1));
    }

    public function test_voter_nisn_must_be_ten_digits_and_unique(): void
    {
        $user = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);
        $election = $this->makeElection();

        $payload = ['election_id' => $election->id, 'name' => 'Pemilih', 'grade' => 'X', 'is_active' => true];

        $this->actingAs($user)->post('/voters', $payload + ['nisn' => '12345'])->assertSessionHasErrors('nisn');
        $this->actingAs($user)->post('/voters', $payload + ['nisn' => '12345abcde'])->assertSessionHasErrors('nisn');
        $this->actingAs($user)->post('/voters', $payload + ['nisn' => '1234567890'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/voters', $payload + ['nisn' => '1234567890'])->assertSessionHasErrors('nisn');
    }

    public function test_voter_password_is_generated_unique_and_immutable(): void
    {
        $election = $this->makeElection();
        $voters = collect(range(1, 5))->map(fn ($i) => Voter::create([
            'election_id' => $election->id,
            'nisn' => str_pad((string) $i, 10, '0', STR_PAD_LEFT),
            'name' => "Pemilih {$i}",
            'grade' => 'X',
            'is_active' => true,
        ]));

        $this->assertCount(5, $voters->pluck('password')->unique());
        $voters->each(fn ($voter) => $this->assertMatchesRegularExpression('/^\d{6}\*$/', $voter->password));

        $original = $voters[0]->password;
        $voters[0]->forceFill(['password' => '000000*', 'name' => 'Baru'])->save();

        $this->assertSame($original, $voters[0]->fresh()->password);
        $this->assertSame('Baru', $voters[0]->fresh()->name);
    }

    public function test_tables_can_be_sorted_by_column(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $election = $this->makeElection();

        foreach (['Budi' => '0000000001', 'Andi' => '0000000002', 'Citra' => '0000000003'] as $name => $nisn) {
            Voter::create(['election_id' => $election->id, 'nisn' => $nisn, 'name' => $name, 'grade' => 'X', 'is_active' => true]);
        }

        $this->actingAs($user)->get('/voters?sort=name&direction=asc')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('voters.data.0.name', 'Andi')
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'asc'));

        $this->actingAs($user)->get('/voters?sort=name&direction=desc')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('voters.data.0.name', 'Citra'));

        $this->actingAs($user)->get('/voters?sort=hack;drop&direction=asc')->assertOk();
        $this->actingAs($user)->get('/users?sort=email&direction=desc')->assertOk();
        $this->actingAs($user)->get('/elections?sort=voters_count&direction=desc')->assertOk();
    }

    public function test_voters_can_be_exported_and_template_downloaded(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $election = $this->makeElection();
        Voter::create(['election_id' => $election->id, 'nisn' => '0000000001', 'name' => 'Budi', 'grade' => 'X', 'is_active' => true]);

        $this->actingAs($user)->get('/voters/export?election='.$election->id)->assertOk()->assertDownload();
        $this->actingAs($user)->get('/voters/template')->assertOk()->assertDownload('template_pemilih.xlsx');
    }

    public function test_voters_can_be_imported_from_spreadsheet(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $election = $this->makeElection();

        $make = function (array $rows): UploadedFile {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->fromArray([['No', 'NISN', 'Nama', 'Kelas'], ...$rows], null, 'A1');
            $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
            (new Xlsx($spreadsheet))->save($path);

            return new UploadedFile($path, 'impor.xlsx', null, null, true);
        };

        $this->actingAs($user)->post('/voters/import', [
            'election_id' => $election->id,
            'file' => $make([[1, '123456789', 'Budi', 'X'], [2, '0000000002', 'Andi', 'X']]),
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('voters', 2);
        $this->assertDatabaseHas('voters', ['nisn' => '0123456789', 'name' => 'Budi']);

        $this->actingAs($user)->post('/voters/import', [
            'election_id' => $election->id,
            'file' => $make([[1, '0000000003', 'Citra', 'X'], [2, '0000000002', 'Dup', 'X']]),
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('voters', 2);
    }

    public function test_setting_status_can_be_changed_manually_and_final_locks_data(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $election = $this->makeElection(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
        $this->actingAs($user)->get('/settings');
        $setting = $election->fresh()->setting;

        $this->assertSame('not_started', $setting->status);

        foreach (['ongoing', 'paused', 'closed', 'not_started'] as $status) {
            $this->actingAs($user)->put('/settings', ['status' => $status, 'voting_enabled' => false])->assertSessionHasNoErrors();
            $this->assertSame($status, $setting->fresh()->status);
        }

        $this->actingAs($user)->put('/settings', ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->actingAs($user)->put('/settings', ['status' => 'final', 'voting_enabled' => false])->assertSessionHasNoErrors();

        $this->actingAs($user)->post('/voters', [
            'election_id' => $election->id, 'nisn' => '1234567890', 'name' => 'Budi', 'grade' => 'X', 'is_active' => true,
        ])->assertSessionHas('error');
        $this->actingAs($user)->post('/candidates', ['order_number' => 1, 'chairman_name' => 'Ketua'])->assertSessionHas('error');
        $this->assertDatabaseCount('voters', 0);
        $this->assertDatabaseCount('candidates', 0);

        $this->actingAs($user)->put('/settings', ['status' => 'ongoing', 'voting_enabled' => true])->assertSessionHasNoErrors();
        $this->assertSame('ongoing', $setting->fresh()->status);

        $this->actingAs($user)->post('/candidates', ['order_number' => 1, 'chairman_name' => 'Ketua'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('candidates', 1);
    }

    public function test_regular_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/users')->assertForbidden();
    }
}
