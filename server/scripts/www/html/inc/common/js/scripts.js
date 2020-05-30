// update Information div
function updateInfo(){
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("info").innerHTML = this.responseText;
    }
  };
  xmlhttp.open("GET", "inc/common/information.php", true);
  xmlhttp.send();
}      
