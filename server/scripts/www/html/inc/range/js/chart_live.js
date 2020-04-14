function updateChart() {
  var dps_wifi_bot = readTextFile("json/chart_wifi_bot_rl_" + session_id);
  var dps_wifi_top = readTextFile("json/chart_wifi_top_rl_" + session_id);
  var dps_bt = readTextFile("json/chart_bt_rl_" + session_id);
  chart.options.data[0].dataPoints = JSON.parse(dps_wifi_bot); 
  chart.options.data[1].dataPoints = JSON.parse(dps_wifi_top);
  chart.options.data[2].dataPoints = JSON.parse(dps_bt);
  chart.render();
};

function customizeChart() {
  chart.options.title.text = "In Range - Live";
  chart.render();
  setInterval(function () { updateChart() }, updateInterval);
}
