function buildChart() {
 
  session_id = /SESS\w*ID=([^;]+)/i.test(document.cookie) ? RegExp.$1 : false;

  colorWifi = "#1b81e5";
  colorWifiLocal = "#78bcff";
  colorBluetooth = "#061c33";

  chart = new CanvasJS.Chart("chartContainer", {

    theme: "light2",
    zoomEnabled: true,
    
    title: {
      text: "Monitoring history"
    },
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

  function toggleDataSeries(e){
    if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
      e.dataSeries.visible = false;
    } else {
      e.dataSeries.visible = true;
    }
      chart.render();
  }
}

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

  function updateChart() {
    var dps_wifi_bot = readTextFile("json/chart_wifi_bot_history_" + session_id);
    var dps_wifi_top = readTextFile("json/chart_wifi_top_history_" + session_id);
    var dps_bt = readTextFile("json/chart_bt_history_" + session_id);
    chart.options.data[0].dataPoints = JSON.parse(dps_wifi_bot); 
    chart.options.data[1].dataPoints = JSON.parse(dps_wifi_top);
    chart.options.data[2].dataPoints = JSON.parse(dps_bt);
    chart.render();
  }