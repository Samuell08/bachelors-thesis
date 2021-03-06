function buildChart() {
 
  session_id = /SESS\w*ID=([^;]+)/i.test(document.cookie) ? RegExp.$1 : false;

  nameAB = "A->B";
  nameBA = "B->A";

  colorAB = "#1b81e5";
  colorBA = "#061c33";

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
      title: "Average time [minutes]", // must be same as format (below in toolTip function)
      titleFontColor: colorAB,
      labelFontColor: colorAB,
      gridDashType: "dash",
      tickThickness: 0
    },
    toolTip: {
      shared: true,
      cornerRadius: 15,
      fontWeight: "bold",
      contentFormatter: function (e) {

          var format = "m"; // must be same as in PHP
          var divisorS;
          var divisorM;
          var divisorH;          

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
              case nameAB: color = colorAB; break;
              case nameBA: color = colorBA; break;
              default:     color = "#000000"; break;
            }
            switch (format) {
              case "s":
                divisorS = 1;
                divisorM = 60;
                divisorH = 3600;
                break;
              case "m":
                divisorS = 1/60;
                divisorM = 1;
                divisorH = 60;
                break;
              case "h":
                divisorS = 1/3600;
                divisorM = 1/60;
                divisorH = 1;
                break;
              default:
                divisorS = 1;
                divisorM = 60;
                divisorH = 3600;
                break;
            }
            content += "<span style='color:" + color + "'>" + 
                         e.entries[i].dataSeries.name                   + ":<br>" + 
                         (e.entries[i].dataPoint.y/divisorS).toFixed(2) + " second(s)<br>" +
                         (e.entries[i].dataPoint.y/divisorM).toFixed(2) + " minutes(s)<br>" +
                         (e.entries[i].dataPoint.y/divisorH).toFixed(2) + " hour(s)" +
                       "</span>";
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
      type: "line",
      name: nameAB,
      color: colorAB,
      showInLegend: true,
      xValueType: "dateTime",
      xValueFormatString: "D.M H:mm:ss",
      yValueFormatString: "#",
      connectNullData: true,
      dataPoints: [{"x":1000,"y":0}]
    },{
      type: "line",
      name: nameBA,
      color: colorBA,
      showInLegend: true,
      xValueType: "dateTime",
      yValueFormatString: "#",
      connectNullData: true,
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
