function updateChart() {
  var dps_wifi_unique = readTextFile("json/chart_wifi_unique_pl_" + session_id);
  var dps_wifi_total = readTextFile("json/chart_wifi_total_pl_" + session_id);
  //var dps_bt = readTextFile("json/chart_bt_" + session_id);
  chartWifi.options.data[0].dataPoints = JSON.parse(dps_wifi_unique); 
  chartWifi.options.data[1].dataPoints = JSON.parse(dps_wifi_total);
  //chartBluetooth.options.data[0].dataPoints = JSON.parse(dps_bt);
  chartWifi.render();
  chartBluetooth.render();
};

function customizeChart() {
  chartWifi.options.title.text = chartWifi.options.title.text + " - Live";
  chartBluetooth.options.title.text = chartBluetooth.options.title.text + " - Live";
  chartWifi.render();
  chartBluetooth.render();
  setInterval(function () { updateChart() }, updateInterval);
}
