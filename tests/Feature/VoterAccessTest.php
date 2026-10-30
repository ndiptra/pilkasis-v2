<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class VoterAccessTest extends TestCase
{
    use RefreshDatabase;

    private Election $election;

    private Voter $voter;

    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->election = Election::create([
            'name' => 'Pemilihan Contoh',
            'year' => '2026',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $this->election->setting()->create(['status' => 'ongoing', 'voting_enabled' => true]);

        $this->voter = Voter::create([
            'election_id' => $this->election->id,
            'nisn' => '0012345678',
            'name' => 'Siswa Contoh',
            'grade' => 'XII IPA 1',
            'is_active' => true,
        ]);

        $this->candidate = Candidate::create([
            'election_id' => $this->election->id,
            'order_number' => 1,
            'chairman_name' => 'Ketua Contoh',
            'vice_chairman_name' => 'Wakil Contoh',
        ]);
    }

    private function loginAs(Voter $voter): void
    {
        $this->post('/voter/login', ['nisn' => $voter->nisn, 'password' => $voter->password])
            ->assertRedirect('/vote');
    }

    public function test_voter_login_page_is_accessible(): void
    {
        $this->get('/voter/login')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Voter/Login'));
    }

    public function test_student_can_login_with_nisn_and_password(): void
    {
        $this->loginAs($this->voter);

        $this->get('/vote')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Voter/Ballot')
                ->where('idleSeconds', 300)
                ->has('candidates', 1)
                ->missing('results'));
    }

    public function test_non_student_can_login_with_four_digit_code(): void
    {
        $teacher = Voter::create([
            'election_id' => $this->election->id,
            'nisn' => '1234',
            'name' => 'Guru Contoh',
            'grade' => 'Guru',
            'is_active' => true,
        ]);

        $this->loginAs($teacher);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => '000000*'])
            ->assertSessionHasErrors('nisn');

        $this->get('/vote')->assertRedirect('/voter/login');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        RateLimiter::clear('voter-login:127.0.0.1');

        foreach (range(1, 10) as $ignored) {
            $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => 'salah']);
        }

        $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => $this->voter->password])
            ->assertSessionHasErrors('nisn');
        $this->get('/vote')->assertRedirect('/voter/login');
    }

    public function test_voter_cannot_login_unless_election_is_ongoing(): void
    {
        foreach (['not_started', 'paused', 'closed', 'final'] as $status) {
            $this->election->setting()->update(['status' => $status]);

            $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => $this->voter->password])
                ->assertSessionHasErrors('nisn');
        }
    }

    public function test_voter_cannot_login_when_voting_is_disabled(): void
    {
        $this->election->setting()->update(['voting_enabled' => false]);

        $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => $this->voter->password])
            ->assertSessionHasErrors('nisn');
    }

    public function test_account_is_locked_after_repeated_wrong_passwords(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => '000000*']);
        }

        $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => $this->voter->password])
            ->assertSessionHasErrors('nisn');
        $this->get('/vote')->assertRedirect('/voter/login');
    }

    public function test_inactive_voter_cannot_login(): void
    {
        $this->voter->update(['is_active' => false]);

        $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => $this->voter->password])
            ->assertSessionHasErrors('nisn');
    }

    public function test_voting_records_vote_and_shows_thanks_page_once(): void
    {
        $this->loginAs($this->voter);

        $this->post('/vote', ['candidate_id' => $this->candidate->id])->assertRedirect('/vote/thanks');

        $this->assertDatabaseHas('votes', [
            'voter_id' => $this->voter->id,
            'candidate_id' => $this->candidate->id,
            'election_id' => $this->election->id,
        ]);
        $this->assertTrue($this->voter->fresh()->has_voted);

        $this->get('/vote/thanks')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Voter/Thanks')->where('name', 'Siswa Contoh'));

        $this->get('/vote/thanks')->assertRedirect('/voter/login');
        $this->get('/vote')->assertRedirect('/voter/login');
    }

    public function test_voter_who_already_voted_cannot_login_again(): void
    {
        $this->loginAs($this->voter);
        $this->post('/vote', ['candidate_id' => $this->candidate->id]);

        $this->post('/voter/login', ['nisn' => $this->voter->nisn, 'password' => $this->voter->password])
            ->assertSessionHasErrors('nisn');

        $this->assertSame(1, Vote::count());
    }

    public function test_voter_cannot_vote_twice_with_existing_session(): void
    {
        $this->loginAs($this->voter);
        $this->post('/vote', ['candidate_id' => $this->candidate->id]);

        $this->withSession(['voter_id' => $this->voter->id, 'voter_last_activity' => now()->timestamp])
            ->post('/vote', ['candidate_id' => $this->candidate->id])
            ->assertRedirect('/voter/login');

        $this->assertSame(1, Vote::count());
    }

    public function test_voter_cannot_vote_when_election_is_paused_mid_session(): void
    {
        $this->loginAs($this->voter);
        $this->election->setting()->update(['status' => 'paused']);

        $this->post('/vote', ['candidate_id' => $this->candidate->id])->assertRedirect('/voter/login');

        $this->assertSame(0, Vote::count());
    }

    public function test_voter_cannot_vote_for_candidate_of_another_election(): void
    {
        $other = Election::create([
            'name' => 'Lain',
            'year' => '2025',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'is_active' => false,
        ]);
        $foreign = Candidate::create([
            'election_id' => $other->id,
            'order_number' => 2,
            'chairman_name' => 'Ketua Lain',
        ]);

        $this->loginAs($this->voter);

        $this->post('/vote', ['candidate_id' => $foreign->id])->assertRedirect();

        $this->assertSame(0, Vote::count());
    }

    public function test_session_expires_after_idle_period(): void
    {
        $this->withSession([
            'voter_id' => $this->voter->id,
            'voter_last_activity' => now()->subMinutes(6)->timestamp,
        ])->get('/vote')->assertRedirect('/voter/login');
    }

    public function test_expired_logout_returns_to_voter_access(): void
    {
        $this->loginAs($this->voter);

        $this->post('/voter/logout', ['expired' => true])->assertRedirect('/voter/login');
        $this->get('/vote')->assertRedirect('/voter/login');
    }
}
