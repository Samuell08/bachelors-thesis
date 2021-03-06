function buildChart() {
 
  session_id = /SESS\w*ID=([^;]+)/i.test(document.cookie) ? RegExp.$1 : false;

  nameWifiUnique = "Unique";
  nameWifiTotal  = "Total";
  nameBtUnique   = "Unique";
  nameBtTotal    = "Total";

  colorWifiUnique = "#1b81e5";
  colorWifiTotal  = "#e57f1b";
  colorBtUnique   = "#3b5998";
  colorBtTotal    = "#987a3b";

  chartWifi = new CanvasJS.Chart("chartContainerWifi", {

    theme: "light2",
    zoomEnabled: true,
    
    title: {
      text: "Wi-Fi Passages"
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
      titleFontColor: colorWifiUnique,
      labelFontColor: colorWifiUnique,
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
          
          for (var i = 0; i < e.entries.length; i++) {
            var color;
            switch (e.entries[i].dataSeries.name) {
              case nameWifiUnique: color = colorWifiUnique; break;
              case nameWifiTotal:  color = colorWifiTotal; break;
              default:             color = "#000000"; break;
            }
            content += "<span style='color:" + color + "'>" + e.entries[i].dataSeries.name + ": " + e.entries[i].dataPoint.y + "</span>";
            content += "<br/>";
          }
          return content;
        }
    },
    legend: {
      cursor: "pointer",
      itemclick: toggleDataSeries
    },
    data: [{
      type: "column",
      name: nameWifiUnique,
      color: colorWifiUnique,
      showInLegend: true,
      xValueType: "dateTime",
      xValueFormatString: "D.M H:mm:ss",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    },{
      type: "column",
      name: nameWifiTotal,
      color: colorWifiTotal,
      showInLegend: true,
      xValueType: "dateTime",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    }]
  });
  
  chartBluetooth = new CanvasJS.Chart("chartContainerBluetooth", {

    theme: "light2",
    zoomEnabled: true,
    
    title: {
      text: "Bluetooth Passages"
    },
    axisX: {
      title: "Timestamp",
      valueFormatString: "D.M H:mm",
      gridThickness: 1,
      gridDashType: "dash",
      labelAngle: -45
    },
    axisY: {
      title: "Bluetooth devices",
      titleFontColor: colorBtUnique,
      labelFontColor: colorBtUnique,
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
          
          for (var i = 0; i < e.entries.length; i++) {
            var color;
            switch (e.entries[i].dataSeries.name) {
              case nameBtUnique: color = colorBtUnique; break;
              case nameBtTotal:  color = colorBtTotal; break;
              default:           color = "#000000"; break;
            }
            content += "<span style='color:" + color + "'>" + e.entries[i].dataSeries.name + ": " + e.entries[i].dataPoint.y + "</span>";
            content += "<br/>";
          }
          return content;
        }
    },
    legend: {
      cursor: "pointer",
      itemclick: toggleDataSeries
    },
    data: [{
      type: "column",
      name: nameBtUnique,
      color: colorBtUnique,
      showInLegend: true,
      xValueType: "dateTime",
      xValueFormatString: "D.M H:mm:ss",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    },{
      type: "column",
      name: nameBtTotal,
      color: colorBtTotal,
      showInLegend: true,
      xValueType: "dateTime",
      yValueFormatString: "#",
      dataPoints: [{"x":1000,"y":0}]
    }]
  });

  chartWifi.render();
  chartBluetooth.render();

  function toggleDataSeries(e){
    if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
      e.dataSeries.visible = false;
    } else {
      e.dataSeries.visible = true;
    }
      chartWifi.render();
      chartBluetooth.render();
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
