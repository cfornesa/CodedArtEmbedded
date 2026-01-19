document.addEventListener('DOMContentLoaded', function () {
    const pieceImages = document.querySelectorAll('.piece-img');

    function resizeImages() {
      let widthPercent;

      if (window.innerWidth > 1440) {
        widthPercent = 0.20;
      } else if (window.innerWidth > 992) {
        widthPercent = 0.25;
      } else {
        widthPercent = 0.7;
      }

      const newSize = (window.innerWidth * widthPercent) + 'px';

      pieceImages.forEach(function (img) {
        img.style.width = newSize;
        img.style.height = newSize;
      });
    }

    resizeImages(); // Initial
    window.addEventListener('resize', resizeImages); // On resize
  });