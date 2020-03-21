function buildChart() {
 
  var updateInterval = "30000"; 
  var session_id = /SESS\w*ID=([^;]+)/i.test(document.cookie) ? RegExp.$1 : false;

  var colorWifi = "#1b81e5";
  var colorWifiLocal = "#78bcff";
  var colorBluetooth = "#061c33";

  var chart = new CanvasJS.Chart("chartContainer", {

    theme: "light2",
    zoomEnabled: true,
    
    title: {
      text: "Monitoring results"
    },
    subtitles: [{
      text: "updated every " + updateInterval/1000 + " seconds"
    }],
    axisX: {
      title: "Timestamp",
      valueFormatString: "D.M H:mm",
      gridThickness: 1,
      gridDashType: "dash",
      labelAngle: -45
    },
    axisY: {
      title: "Wi-Fi devices",
      titleFontColor: colorWifi,
      labelFontColor: colorWifi,
      gridDashType: "dash",
      tickThickness: 0
    },
    axisY2: {
      title: "Bluetooth devices",
      titleFontColor: colorBluetooth,
      labelFontColor: colorBluetooth,
      gridDashType: "dash",
      tickThickness: 0
    },
    toolTip: {
      shared: true,
      cornerRadius: 15
    },
    legend: {
      cursor: "pointer",
      itemclick: toggleDataSeries
    },
    data: [{
      type: "stackedArea",
      name: "Global MAC",
      color: colorWifi,
      showInLegend: true,
      toolTipContent: "{x} <hr> <span style=\"color:" + colorWifi + "\"><b>{name}: </b></span> {y}",
      xValueType: "dateTime",
      xValueFormatString: "D.M H:mm:ss",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    },{
      type: "stackedArea",
      name: "Local MAC unique",
      color: colorWifiLocal,
      showInLegend: true,
      toolTipContent: "<span style=\"color:" + colorWifiLocal + "\"><b>{name}: </b></span> {y} <br> <b>Total estimated: #total</b>",
      xValueType: "dateTime",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    },{
      type: "line",
      axisYType: "secondary",
      name: "Bluetooth",
      color: colorBluetooth,
      markerType: "square",
      showInLegend: true,
      toolTipContent: "<hr> <span style=\"color:" + colorBluetooth + "\"><b>{name}: </b></span> {y}",
      xValueType: "dateTime",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    }]
  });

  chart.render();
  updateChart();

  function readTextFile(file) {
    var rawFile = new XMLHttpRequest();
    // when file does not exists yet, return default value
    var retVal = '[{"x":1000,"y":0}]';
    rawFile.open("GET", file, false);
    rawFile.onreadystatechange = function () {
      if(rawFile.readyState === 4) {
        if(rawFile.status === 200 || rawFile.status == 0) {
          var allText = rawFile.responseText;
            retVal = String(allText);
          }
        }
      }
    rawFile.send(null);
    return retVal;
  }

  function toggleDataSeries(e){
    if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
      e.dataSeries.visible = false;
    } else {
      e.dataSeries.visible = true;
    }
      chart.render();
    }

  function updateChart() {
    var dps_wifi_bot = readTextFile("json/chart_wifi_bot_" + session_id);
    var dps_wifi_top = readTextFile("json/chart_wifi_top_" + session_id);
    var dps_bt = readTextFile("json/chart_bt_" + session_id);
    chart.options.data[0].dataPoints = JSON.parse(dps_wifi_bot); 
    chart.options.data[1].dataPoints = JSON.parse(dps_wifi_top);
    chart.options.data[2].dataPoints = JSON.parse(dps_bt);
    chart.render();
  };
        
  setInterval(function () { updateChart() }, updateInterval);
}
