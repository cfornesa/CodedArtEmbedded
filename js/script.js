function setName(currentHost){
  if(currentHost == "cfornesa.com"){
    return "CFornesa";
  } else if(currentHost == "fornesus.com"){
    return "Fornesus";
  } else {
    return "Unknown";
  }
}

document.title = setName(window.location.host);