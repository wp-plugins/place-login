(function(swra){
  function swrb(){
    var e=swra("#pass1").val(),d=swra("#user_login").val(),c=swra("#pass2").val(),f;
    swra("#pass-strength-result").removeClass("short bad good strong");
    if(!e){swra("#pass-strength-result").html("Strength indicator");return}
    f=passwordStrength(e,d,c);
    switch(f){
      case 2:swra("#pass-strength-result").addClass("bad").html("Weak");
      break;
      case 3:swra("#pass-strength-result").addClass("good").html("Medium");
      break;
      case 4:swra("#pass-strength-result").addClass("strong").html("Strong");
      break;
      case 5:swra("#pass-strength-result").addClass("short").html("Mismatch");
      break;
      default:swra("#pass-strength-result").addClass("short").html("Very weak")
    }
  }
  swra(document).ready(function(){
    swra("#pass1").val("").keyup(swrb);
    swra("#pass2").val("").keyup(swrb);
  })
})(jQuery);