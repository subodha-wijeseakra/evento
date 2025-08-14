<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Centered 3-Tile Carousel</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f0f0f0;
    }

    .carousel-wrapper {
      width: 80%;
      margin: 50px auto;
      overflow: hidden;
      position: relative;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .carousel-track {
      display: flex;
      gap: 20px;
      transition: transform 0.5s ease-in-out;
      padding: 20px;
    }

    .carousel-item {
      flex: 0 0 calc((100% - 40px) / 3); /* 3 items with 2 gaps of 20px */
    }

    .carousel-item img {
      width: 100%;
      height: 280px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .carousel-controls {
      position: absolute;
      top: 50%;
      width: 100%;
      display: flex;
      justify-content: space-between;
      transform: translateY(-50%);
      padding: 0 10px;
    }

    .carousel-button {
      background-color: rgba(0, 0, 0, 0.5);
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      padding: 8px 14px;
      border-radius: 50%;
      transition: background 0.2s ease;
    }

    .carousel-button:hover {
      background-color: rgba(0, 0, 0, 0.7);
    }

    @media (max-width: 768px) {
      .carousel-item {
        flex: 0 0 100%;
      }

      .carousel-track {
        gap: 10px;
        padding: 10px;
      }
    }
  </style>
</head>
<body>

<div class="carousel-wrapper">
  <div class="carousel-track" id="carouselTrack">
    <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
     <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
      <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
       <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
        <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
         <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
          <div class="carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
          
    <div class="carousel-item"><img src="https://via.placeholder.com/800x300?text=Slide+2" alt="Slide 2"></div>
    <div class="carousel-item"><img src="https://via.placeholder.com/800x300?text=Slide+3" alt="Slide 3"></div>
    <div class="carousel-item"><img src="https://via.placeholder.com/800x300?text=Slide+4" alt="Slide 4"></div>
    <div class="carousel-item"><img src="https://via.placeholder.com/800x300?text=Slide+5" alt="Slide 5"></div>
    <div class="carousel-item"><img src="https://via.placeholder.com/800x300?text=Slide+6" alt="Slide 6"></div>
  </div>

  <div class="carousel-controls">
    <button class="carousel-button" id="prevBtn">&#10094;</button>
    <button class="carousel-button" id="nextBtn">&#10095;</button>
  </div>
</div>

<script>
  const track = document.getElementById('carouselTrack');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  let position = 0;

  function getVisibleItems() {
    return window.innerWidth >= 768 ? 3 : 1;
  }

  function updateCarousel() {
    const totalItems = track.children.length;
    const visibleItems = getVisibleItems();
    const itemWidth = track.children[0].offsetWidth + parseFloat(getComputedStyle(track).gap) || 0;
    const maxPosition = totalItems - visibleItems;
    position = Math.max(0, Math.min(position, maxPosition));
    track.style.transform = `translateX(-${position * itemWidth}px)`;
  }

  nextBtn.addEventListener('click', () => {
    position++;
    updateCarousel();
  });

  prevBtn.addEventListener('click', () => {
    position--;
    updateCarousel();
  });

  window.addEventListener('resize', updateCarousel);
  window.addEventListener('load', updateCarousel);
</script>

</body>
</html>
