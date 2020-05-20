function updateChart() {
  var dps_wifi_unique = readTextFile("json/chart_wifi_unique_mh_" + session_id);
  var dps_wifi_total  = readTextFile("json/chart_wifi_total_mh_" + session_id);
  var dps_bt_unique   = readTextFile("json/chart_bt_unique_mh_" + session_id);
  var dps_bt_total    = readTextFile("json/chart_bt_total_mh_" + session_id);
  chartWifi.options.data[0].dataPoints = JSON.parse(dps_wifi_unique); 
  chartWifi.options.data[1].dataPoints = JSON.parse(dps_wifi_total);
  chartBluetooth.options.data[0].dataPoints = JSON.parse(dps_bt_unique);
  chartBluetooth.options.data[1].dataPoints = JSON.parse(dps_bt_total);
  chartWifi.render();
  chartBluetooth.render();
}

function customizeChart() {
  chartWifi.options.title.text = chartWifi.options.title.text + " - History";
  chartBluetooth.options.title.text = chartBluetooth.options.title.text + " - History";
  chartWifi.render();
  chartBluetooth.render();
}
