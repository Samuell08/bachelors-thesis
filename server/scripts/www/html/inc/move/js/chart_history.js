function updateChart() {
  var dps_AB = readTextFile("json/chart_AB_mh_" + session_id);
  var dps_BA = readTextFile("json/chart_BA_mh_" + session_id);
  chart.options.data[0].dataPoints = JSON.parse(dps_AB); 
  chart.options.data[1].dataPoints = JSON.parse(dps_BA);
  chart.render();
}

function customizeChart() {
  chart.options.title.text = chart.options.title.text + " - History";
  chart.render();
}
