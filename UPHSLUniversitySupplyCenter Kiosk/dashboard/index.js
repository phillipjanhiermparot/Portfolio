const sideMenu = document.querySelector("aside");
const menuBtn = document.querySelector("#menu-btn");
const closeBtn = document.querySelector("#close-btn");
const themeToggler = document.querySelector(".theme-toggler");

menuBtn.addEventListener('click', () =>{
    sideMenu.style.display = 'block';
})

closeBtn.addEventListener('click', () =>{
    sideMenu.style.display = 'none';
})

themeToggler.addEventListener('click', () => {
    document.body.classList.toggle('dark-theme-variables');
})

function dateFilter(time, chartId) {
    const chart = document.getElementById(chartId).getContext('2d');
    switch (time) {
        case 'week':
            chart.config.options.scales.x.time.unit = 'week';
            break;
        case 'month':
            chart.config.options.scales.x.time.unit = 'month';
            break;
        case 'year':
            chart.config.options.scales.x.time.unit = 'year';
            break;
    }
    chart.update();
}

// CAS CHART setup
const casData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(255, 99, 132)',
        borderColor: 'rgb(255, 99, 132)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const casConfig = {
    type: 'line',
    data: casData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render CAS chart
const casChart = new Chart(
    document.getElementById('CAS'),
    casConfig
);

// CBA CHART setup
const cbaData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(54, 162, 235)',
        borderColor: 'rgb(54, 162, 235)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const cbaConfig = {
    type: 'line',
    data: cbaData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render CBA chart
const cbaChart = new Chart(
    document.getElementById('CBA'),
    cbaConfig
);

// CCS CHART setup
const ccsData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(255, 206, 86)',
        borderColor: 'rgb(255, 206, 86)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const ccsConfig = {
    type: 'line',
    data: ccsData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render CCS chart
const ccsChart = new Chart(
    document.getElementById('CCS'),
    ccsConfig
);

// CRIM CHART setup
const crimData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(75, 192, 192)',
        borderColor: 'rgb(75, 192, 192)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const crimConfig = {
    type: 'line',
    data: crimData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render CRIM chart
const crimChart = new Chart(
    document.getElementById('CRIM'),
    crimConfig
);

const ceducData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(153, 102, 255)',
        borderColor: 'rgb(153, 102, 255)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const ceducConfig = {
    type: 'line',
    data: ceducData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render CEDUC chart
const ceducChart = new Chart(
    document.getElementById('CEDUC'),
    ceducConfig
);

// CIHM CHART setup
const cihmData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(255, 205, 86)',
        borderColor: 'rgb(255, 205, 86)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const cihmConfig = {
    type: 'line',
    data: cihmData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render CIHM chart
const cihmChart = new Chart(
    document.getElementById('CIHM'),
    cihmConfig
);

// MARITIME CHART setup
const maritimeData = {
    datasets: [{
        label: 'Sales',
        backgroundColor: 'rgb(255, 99, 132)',
        borderColor: 'rgb(255, 99, 132)',
        data: [
            { x: new Date('2024-01-01T00:00:00'), y: 20 },
            { x: new Date('2024-01-01T01:00:00'), y: 40 },
            { x: new Date('2024-01-01T02:00:00'), y: 60 },
            { x: new Date('2024-01-02T00:00:00'), y: 80 },
            { x: new Date('2025-01-26T00:00:00'), y: 100 }
        ]
    }]
};

const maritimeConfig = {
    type: 'line',
    data: maritimeData,
    options: {
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'week'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
};

// Render MARITIME chart
const maritimeChart = new Chart(
    document.getElementById('MARITIME'),
    maritimeConfig
);

// Instantly assign Chart.js version
const chartVersion = document.getElementById('chartVersion');
chartVersion.innerText = Chart.version;

