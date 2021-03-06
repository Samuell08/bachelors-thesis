function updateChart() {
  var dps_wifi_bot = readTextFile("json/chart_wifi_bot_rh_" + session_id);
  var dps_wifi_top = readTextFile("json/chart_wifi_top_rh_" + session_id);
  var dps_bt = readTextFile("json/chart_bt_rh_" + session_id);
  chart.options.data[0].dataPoints = JSON.parse(dps_wifi_bot); 
  chart.options.data[1].dataPoints = JSON.parse(dps_wifi_top);
  chart.options.data[2].dataPoints = JSON.parse(dps_bt);
  chart.render();
}

function customizeChart() {
  chart.options.title.text = "In Range - History";
  chart.render();
}
