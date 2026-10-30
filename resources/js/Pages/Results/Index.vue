<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import NoElectionAlert from '../../Components/NoElectionAlert.vue';

const props = defineProps({
    results: {
        type: Array,
        default: () => [],
    },
    election: {
        type: Object,
        default: null,
    },
    summary: {
        type: Object,
        required: true,
    },
    chart: {
        type: Object,
        required: true,
    },
});

const statBoxes = computed(() => [
    { label: 'Total Suara Masuk', value: props.summary.total_votes, color: 'bg-primary', icon: 'fa-vote-yea', href: '/voters', footer: 'Data pemilih' },
    { label: 'Total Pemilih', value: props.summary.total_voters, color: 'bg-info', icon: 'fa-users', href: '/voters', footer: 'Kelola data' },
    { label: 'Partisipasi Pemilih', value: `${props.summary.participation}%`, color: 'bg-success', icon: 'fa-chart-pie', href: '/settings', footer: 'Pengaturan' },
]);

const chartCanvas = ref(null);
let chartInstance = null;

onMounted(() => {
    if (!window.Chart || !chartCanvas.value) {
        return;
    }

    chartInstance = new window.Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: props.chart.labels,
            datasets: [
                {
                    label: 'Jumlah Suara',
                    data: props.chart.data,
                    backgroundColor: 'rgba(0, 86, 167, 0.75)',
                    borderColor: 'rgba(0, 86, 167, 1)',
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: false,
            },
            scales: {
                yAxes: [
                    {
                        ticks: {
                            beginAtZero: true,
                            precision: 0,
                        },
                    },
                ],
            },
        },
    });
});

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});
</script>

<template>
    <Head title="Hasil" />

    <NoElectionAlert v-if="!election" />

    <div class="row">
        <div v-for="box in statBoxes" :key="box.label" class="col-lg-4 col-12">
            <div class="small-box" :class="box.color">
                <div class="inner">
                    <h3>{{ box.value }}</h3>
                    <p>{{ box.label }}</p>
                </div>
                <div class="icon">
                    <i class="fas" :class="box.icon"></i>
                </div>
                <Link :href="box.href" class="small-box-footer">
                    {{ box.footer }} <i class="fas fa-arrow-circle-right"></i>
                </Link>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Grafik Perolehan Suara</h3>
                    <div v-if="election" class="card-tools text-muted">
                        {{ election.name }} ({{ election.year }})
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="results.length === 0" class="text-center text-muted py-5">
                        Belum ada pasangan calon untuk ditampilkan.
                    </div>
                    <div v-else class="results-chart">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rekapitulasi</h3>
                </div>
                <div class="card-body">
                    <div v-if="results.length === 0" class="text-center text-muted py-4">
                        Belum ada data hasil.
                    </div>
                    <div
                        v-for="result in results"
                        :key="result.id"
                        class="dashboard-candidate"
                    >
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="min-width-0">
                                <span class="badge badge-primary mr-1">{{ result.order_number }}</span>
                                <strong>{{ result.chairman_name }}</strong>
                                <div class="small text-muted">{{ result.vice_chairman_name || '-' }}</div>
                            </div>
                            <div class="text-right ml-2">
                                <strong>{{ result.votes }}</strong>
                                <div class="small text-muted">{{ result.percentage }}%</div>
                            </div>
                        </div>
                        <div class="progress mt-2">
                            <div
                                class="progress-bar bg-primary"
                                role="progressbar"
                                :style="{ width: `${result.percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>