define(['core/chartjs'], function(Chart) {
    "use strict";

    return {
        init: function(params) {
            var canvas = document.getElementById(params.canvasId);
            if (!canvas) { return; }
            var ctx = canvas.getContext('2d');
            
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: params.labels,
                    datasets: [{
                        label: params.datasetLabel,
                        data: [
                            params.data.vis,
                            params.data.sen,
                            params.data.act,
                            params.data.glo,
                            params.data.vrb,
                            params.data.int,
                            params.data.ref,
                            params.data.seq
                        ],
                        backgroundColor: 'rgba(21, 103, 249, 0.2)',
                        borderColor: 'rgba(21, 103, 249, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(21, 103, 249, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        r: {
                            min: 0,
                            max: 11,
                            angleLines: { 
                                display: true,
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            grid: {
                                color: function(context) {
                                    if (context.tick.value % 2 === 1) {
                                        return 'transparent';
                                    }
                                    return 'rgba(0, 0, 0, 0.1)';
                                },
                                lineWidth: 1
                            },
                            ticks: {
                                stepSize: 1,
                                display: true,
                                backdropColor: 'transparent',
                                callback: function(value) {
                                    return value % 2 !== 0 ? value : '';
                                },
                                font: {
                                    size: 12
                                }
                            },
                            pointLabels: {
                                font: {size: 14}
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    };
});
