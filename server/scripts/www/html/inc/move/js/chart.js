function buildChart() {
 
  session_id = /SESS\w*ID=([^;]+)/i.test(document.cookie) ? RegExp.$1 : false;

  nameWifi = "Wi-Fi";
  nameBluetooth = "Bluetooth";

  colorWifi = "#1b81e5";
  colorBluetooth = "#061c33";

  chart = new CanvasJS.Chart("chartContainer", {

    theme: "light2",
    zoomEnabled: true,
    
    title: {
      text: "Movement"
    },
    axisX: {
      title: "Timestamp",
      valueFormatString: "D.M H:mm",
      gridThickness: 1,
      gridDashType: "dash",
      labelAngle: -45
    },
    axisY: {
      title: "Wi-Fi average time",
      titleFontColor: colorWifi,
      labelFontColor: colorWifi,
      gridDashType: "dash",
      tickThickness: 0
    },
    axisY2: {
      title: "Bluetooth average time",
      titleFontColor: colorBluetooth,
      labelFontColor: colorBluetooth,
      gridDashType: "dash",
      tickThickness: 0
    },
    toolTip: {
      shared: true,
      cornerRadius: 15,
      fontWeight: "bold",
      contentFormatter: function (e) {

          var timestamp = new Date(e.entries[0].dataPoint.x);
          var YYYY = timestamp.getFullYear();
          var MM = timestamp.getMonth(); MM++; MM = "0" + MM; MM = MM.substr(-2);
          var DD = "0" + timestamp.getDate(); DD = DD.substr(-2);
          var HOD = "0" + timestamp.getHours(); HOD = HOD.substr(-2);
          var MIN = "0" + timestamp.getMinutes(); MIN = MIN.substr(-2);
          var SEC = "0" + timestamp.getSeconds(); SEC = SEC.substr(-2);

          var content = YYYY + "-" + MM + "-" + DD + " " + HOD + ":" + MIN + ":" + SEC + "<hr>";
          var totalWifi = -1;
          
          for (var i = 0; i < e.entries.length; i++) {
            var color;
            switch (e.entries[i].dataSeries.name) {
              case nameWifi:      color = colorWifi;      totalWifi += e.entries[i].dataPoint.y; break;
              case nameBluetooth: color = colorBluetooth; break;
              default:            color = "#000000"; break;
            }
            content += "<span style='color:" + color + "'>" + e.entries[i].dataSeries.name + ": " + e.entries[i].dataPoint.y + "</span>";
            content += "<br/>";
          }

          totalWifi++; // init na -1
          if (totalWifi > 0) {
            content += "<hr>" + "total Wi-Fi: " + totalWifi;
          }
          return content;
        }
    },
    legend: {
      cursor: "pointer",
      itemclick: toggleDataSeries
    },
    data: [{
      type: "line",
      name: nameWifi,
      color: colorWifi,
      showInLegend: true,
      xValueType: "dateTime",
      xValueFormatString: "D.M H:mm:ss",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    },{
      type: "line",
      axisYType: "secondary",
      name: nameBluetooth,
      color: colorBluetooth,
      markerType: "square",
      showInLegend: true,
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
