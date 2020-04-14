function updateChart() {
  var dps_wifi_unique = readTextFile("json/chart_wifi_unique_ph_" + session_id);
  var dps_wifi_total  = readTextFile("json/chart_wifi_total_ph_" + session_id);
  //var dps_bt = readTextFile("json/chart_bt_ph_" + session_id);
  chart.options.data[0].dataPoints = JSON.parse(dps_wifi_unique); 
  chart.options.data[1].dataPoints = JSON.parse(dps_wifi_total);
  //chart.options.data[2].dataPoints = JSON.parse(dps_bt);
  chart.render();
}

function customizeChart() {
  chart.options.title.text = "Passages - History";
  chart.render();
}
