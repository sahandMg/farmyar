(function($) {
    
"use strict"; // Start of use strict
  // Smooth scrolling using jQuery easing
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function() {
    if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
      if (target.length) {
        $('html, body').animate({
          scrollTop: (target.offset().top - 48)
        }, 1000, "easeInOutExpo");
        return false;
      }
    }
  });

})(jQuery); // End of use strict

// var sheet = document.createElement('style'),  
//   $rangeInput = $('.range input'),
//   prefs = ['webkit-slider-runnable-track', 'moz-range-track', 'ms-track'];

// document.body.appendChild(sheet);

// var getTrackStyle = function (el) {  
//   var rangeInputWidth = $('#monthRange').width;
//   var rangeInputNum = $('#monthList').length;
//   var dist = rangeInputWidth / rangeInputNum;
//   var curVal = el.value,
//       val = (curVal - 1) * dist,
//       style = '';
  
//   // Set active label
//   $('.range-labels li').removeClass('active selected');
  
//   var curLabel = $('.range-labels').find('li:nth-child(' + curVal + ')');
  
//   curLabel.addClass('active selected');
//   curLabel.prevAll().addClass('selected');
  
//   // Change background gradient
//   for (var i = 0; i < prefs.length; i++) {
//     style += '.range {background: linear-gradient(to right, #37adbf 0%, #37adbf ' + val + '%, #fff ' + val + '%, #fff 100%)}';
//     style += '.range input::-' + prefs[i] + '{background: linear-gradient(to right, #37adbf 0%, #37adbf ' + val + '%, #b2b2b2 ' + val + '%, #b2b2b2 100%)}';
//   }

//   return style;
// }

// $rangeInput.on('input', function () {
//   sheet.textContent = getTrackStyle(this);
// });

// // Change input value on label click
// $('.range-labels li').on('click', function () {
//   var index = $(this).index();
  
//   $rangeInput.val(index + 1).trigger('input');
  
// });  