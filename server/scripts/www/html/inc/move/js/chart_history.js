function updateChart() {
  var dps_wifi = readTextFile("json/chart_wifi_mh_" + session_id);
  var dps_bt   = readTextFile("json/chart_bt_mh_" + session_id);
  chart.options.data[0].dataPoints = JSON.parse(dps_wifi); 
  chart.options.data[1].dataPoints = JSON.parse(dps_bt);
  chart.render();
}

function customizeChart() {
  chart.options.title.text = chart.options.title.text + " - History";
  chart.render();
}
