<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <!-- Include Bootstrap CSS & JS + Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
  --fade-speed: 1s;
  --autoplay-delay: 7000ms;
  --font-main: 'Poppins', sans-serif;
}

body {
  font-family: var(--font-main);
  margin: 0;
}

#fadeSlideshow {
  height: 90vh;
  width: 100%;
  position: relative;
}

.fade-slide {
  position: absolute;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  opacity: 0;
  z-index: 0;
  transition: opacity var(--fade-speed) ease-in-out;
}

.fade-slide.active {
  opacity: 1;
  z-index: 2;
}

.btn-primary {
  background-color: #007bff;
  border: none;
}

.btn-outline-light {
  border: 1px solid #fff;
  color: #fff;
}

.btn-outline-light:hover {
  background-color: #fff;
  color: #000;
}
</style>


</head>
<body>
   

<!-- Google Fonts + Bootstrap -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div id="fadeSlideshow" class="position-relative overflow-hidden">
  <!-- Slides -->
  <div class="fade-slide active" style="background-image: url('Assests/Images/1.png');">
    <div class="container h-100 d-flex align-items-end pb-5" style="max-width: 80%;">
      <div class="text-white">
        <h1 class="fw-semibold">Sahasak Nimawum 2025</h1>
        <p class="mb-3">A celebration of innovation and culture. Join thousands of students, professionals and creators!</p>
        <a href="#" class="btn btn-primary me-2">Buy Ticket</a>
        <a href="#" class="btn btn-outline-light">Learn More</a>
      </div>
    </div>
  </div>

  <div class="fade-slide" style="background-image: url('Assests/Images/2.png');">
    <div class="container h-100 d-flex align-items-end pb-5" style="max-width: 80%;">
      <div class="text-white">
        <h1 class="fw-semibold">Connect & Celebrate</h1>
        <p class="mb-3">Experience inspiring performances and connect with creators nationwide.</p>
        <a href="#" class="btn btn-primary me-2">Buy Ticket</a>
        <a href="#" class="btn btn-outline-light">Learn More</a>
      </div>
    </div>
  </div>

  <!-- Controls -->
  <button id="prevFade" class="btn btn-primary position-absolute top-50 start-0 translate-middle-y ms-3 z-3">&#8592;</button>
  <button id="nextFade" class="btn btn-primary position-absolute top-50 end-0 translate-middle-y me-3 z-3">&#8594;</button>
</div>







<script>
document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".fade-slide");
  const nextBtn = document.getElementById("nextFade");
  const prevBtn = document.getElementById("prevFade");

  let current = 0;
  const total = slides.length;

  function showFadeSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove("active");
    });
    slides[index].classList.add("active");
  }

  function nextSlide() {
    current = (current + 1) % total;
    showFadeSlide(current);
  }

  function prevSlide() {
    current = (current - 1 + total) % total;
    showFadeSlide(current);
  }

  nextBtn.addEventListener("click", nextSlide);
  prevBtn.addEventListener("click", prevSlide);

  // Auto-play every 7 seconds
  setInterval(() => {
    nextSlide();
  }, 7000);
});
</script>



</body>
</html>