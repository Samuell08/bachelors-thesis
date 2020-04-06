function updateTextout(){
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      document.getElementById("textout").innerHTML = this.responseText;
    }
  };
  xmlhttp.open("GET", "inc/pass/textout_live.php", true);
  xmlhttp.send();
}      
