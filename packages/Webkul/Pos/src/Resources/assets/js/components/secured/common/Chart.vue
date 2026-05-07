<template>
    <vue-apex-charts
        :options="chartOptions" 
        :series="chartSeries"
    />
</template>

<script setup>
    import { computed } from 'vue';
    import VueApexCharts from "vue3-apexcharts"; 

    /**
     * Props
     */
    const props = defineProps({
        series: {
            type: Array,
            required: true,
            default: () => []
        },
        
        labels: {
            type: Array,
            required: true,
            default: () => []
        }
    });

    /**
     * Computed properties for reactive chart data
     */
    const chartSeries = computed(() => {
        if (
            props.series.length
            && ! props.series[0]?.data
        ) {
            return [{
                name: 'Series',
                data: [...props.series]
            }];
        }
        
        return props.series.map(series => ({...series}));
    });

    const chartOptions = computed(() => {
        return {
            chart: {
                type: 'line',
                toolbar: {
                    show: false,
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                }
            },

            stroke: {
                width: 2,
                colors: ['#2B6FFF'],
                curve: 'straight',
            },

            markers: {
                size: 4,
                colors: ['#FFFFFF'],
                strokeColor: '#2B6FFF',
                strokeWidth: 2,
            },

            grid: {
                strokeDashArray: 8,
            },

            dataLabels: {
                enabled: false
            },

            yaxis: {
                labels: {
                    style: {
                        colors: '#9F9F9E',
                    },
                },
            },

            xaxis: {
                categories: [...props.labels],
                labels: {
                    style: {
                        colors: '#9F9F9E',
                    }
                }
            }
        };
    });
</script>