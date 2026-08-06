<?php
$file = __DIR__ . '/../admin/dashboard.php';
$content = file_get_contents($file);

$newCharts = <<<'JS'
<script>
var chartGrid = 'rgba(255, 255, 255, 0.08)';
var chartText = '#c8cdd8';

var myChart = document.getElementById('myChart').getContext('2d');
new Chart(myChart, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($year) ?>,
    datasets: [{
      label: 'Blotter records',
      fill: true,
      data: <?php echo json_encode($totalBlotter) ?>,
      pointBackgroundColor: '#6610f2',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 5,
      pointHoverRadius: 7,
      borderWidth: 3,
      borderColor: '#6610f2',
      backgroundColor: 'rgba(102, 16, 242, 0.15)'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    legend: { display: false },
    scales: {
      yAxes: [{
        ticks: {
          fontColor: chartText,
          beginAtZero: true,
          userCallback: function (label) {
            if (Math.floor(label) === label) { return label; }
          }
        },
        gridLines: { color: chartGrid, zeroLineColor: chartGrid }
      }],
      xAxes: [{
        ticks: { fontColor: chartText },
        gridLines: { color: chartGrid }
      }]
    },
    tooltips: {
      backgroundColor: '#1e2433',
      titleFontColor: '#fff',
      bodyFontColor: '#c8cdd8',
      cornerRadius: 8
    }
  }
});
</script>

<script>
new Chart('genderChart', {
  type: 'doughnut',
  data: {
    labels: ['Male', 'Female'],
    datasets: [{
      backgroundColor: ['#0037af', '#20c997'],
      borderColor: '#252b3b',
      borderWidth: 3,
      data: [<?= (int) ($genderMale ?? 0) ?>, <?= (int) ($genderFemale ?? 0) ?>]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutoutPercentage: 65,
    legend: {
      display: true,
      position: 'bottom',
      labels: { fontColor: chartText, padding: 16, boxWidth: 12 }
    },
    tooltips: {
      backgroundColor: '#1e2433',
      cornerRadius: 8
    }
  }
});
</script>

<script>
new Chart('donutChart', {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($official_postition) ?>,
    datasets: [{
      data: <?php echo json_encode($total_per_official) ?>,
      backgroundColor: <?php echo json_encode($position_color) ?>,
      borderColor: '#252b3b',
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutoutPercentage: 55,
    legend: {
      display: true,
      position: 'bottom',
      labels: { fontColor: chartText, fontSize: 11, padding: 12, boxWidth: 10 }
    },
    tooltips: {
      backgroundColor: '#1e2433',
      cornerRadius: 8
    }
  }
});
</script>
JS;

$pattern = '/<script>\s*\n\s*\nlet myChart = document\.getElementById\(\'myChart\'\).*?<\/script>\s*\n<script>\s*\nnew Chart\("donutChart".*?<\/script>/s';
if (!preg_match($pattern, $content)) {
    fwrite(STDERR, "Chart block not found.\n");
    exit(1);
}
$content = preg_replace($pattern, $newCharts, $content, 1);
file_put_contents($file, $content);
echo "Charts updated.\n";
