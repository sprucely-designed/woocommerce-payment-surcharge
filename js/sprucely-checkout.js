document.addEventListener("DOMContentLoaded", function () {
  var checkoutForm = document.querySelector("form.checkout");

  checkoutForm.addEventListener("change", function (event) {
    if (event.target.name === "payment_method") {
      var httpRequest = new XMLHttpRequest();
      httpRequest.onreadystatechange = function () {
        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
          // Trigger checkout update
          document.body.dispatchEvent(new CustomEvent("update_checkout"));
        }
      };

      httpRequest.open("POST", sprucelyAjax.ajaxurl, true);
      httpRequest.setRequestHeader(
        "Content-Type",
        "application/x-www-form-urlencoded; charset=UTF-8"
      );

      var params =
        "action=sprucely_update_surcharge" +
        "&payment_method=" +
        encodeURIComponent(event.target.value) +
        "&nonce=" +
        sprucelyAjax.nonce;

      httpRequest.send(params);
    }
  });
});
