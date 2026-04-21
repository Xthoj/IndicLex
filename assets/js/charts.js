document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js did not load.');
        return;
    }

    const entriesBarChart = document.getElementById('entriesBarChart');
    const dictionaryTypePieChart = document.getElementById('dictionaryTypePieChart');
    const entryStatusPieChart = document.getElementById('entryStatusPieChart');
    const topDictionariesBarChart = document.getElementById('topDictionariesBarChart');

    if (entriesBarChart) {
        new Chart(entriesBarChart, {
            type: 'bar',
            data: {
                labels: entriesBarLabels,
                datasets: [{
                    label: 'Active Entries',
                    data: entriesBarValues,
                    backgroundColor: [
                        '#2563eb', '#16a34a', '#ea580c', '#9333ea', '#dc2626',
                        '#0891b2', '#ca8a04', '#4f46e5', '#059669', '#be185d'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    if (dictionaryTypePieChart) {
        new Chart(dictionaryTypePieChart, {
            type: 'pie',
            data: {
                labels: typePieLabels,
                datasets: [{
                    data: typePieValues,
                    backgroundColor: [
                        '#2563eb', '#16a34a', '#ea580c', '#9333ea',
                        '#dc2626', '#0891b2', '#ca8a04', '#4f46e5'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    if (entryStatusPieChart) {
        new Chart(entryStatusPieChart, {
            type: 'pie',
            data: {
                labels: statusPieLabels,
                datasets: [{
                    data: statusPieValues,
                    backgroundColor: ['#16a34a', '#dc2626']
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    if (topDictionariesBarChart) {
        new Chart(topDictionariesBarChart, {
            type: 'bar',
            data: {
                labels: topBarLabels,
                datasets: [{
                    label: 'Active Entries',
                    data: topBarValues,
                    backgroundColor: [
                        '#4f46e5', '#2563eb', '#0891b2', '#16a34a', '#ca8a04',
                        '#ea580c', '#dc2626', '#be185d', '#9333ea', '#059669'
                    ]
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }
});