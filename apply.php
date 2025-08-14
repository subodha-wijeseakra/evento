<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $agreed = isset($_POST['agree']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO event_applications (title, description, agreed_terms) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $description, $agreed);
    $stmt->execute();
    $event_id = $stmt->insert_id;

    $upload_dir = "uploads/event_$event_id/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $proposalPath = "";
    if (!empty($_FILES['proposal']['name'])) {
        $proposalPath = $upload_dir . basename($_FILES['proposal']['name']);
        move_uploaded_file($_FILES['proposal']['tmp_name'], $proposalPath);
    }

    $imagePaths = ["", "", ""];
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES["image$i"]['name'])) {
            $imagePaths[$i] = $upload_dir . basename($_FILES["image$i"]['name']);
            move_uploaded_file($_FILES["image$i"]['tmp_name'], $imagePaths[$i]);
        }
    }

    $stmt = $conn->prepare("UPDATE event_applications SET proposal_file = ?, image1 = ?, image2 = ?, image3 = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $proposalPath, $imagePaths[0], $imagePaths[1], $imagePaths[2], $event_id);
    $stmt->execute();

    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Create Event Application</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
  .step { display: none; }
  .step.active { display: block; }

  .step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
  }

  .step-indicator div {
    flex: 1;
    text-align: center;
    padding: 10px;
    border-bottom: 4px solid lightgray;
    cursor: pointer;
    color: gray;
  }

  .step-indicator .active {
    border-bottom-color: #007bff;
    font-weight: bold;
    color: #007bff;
  }

  .step-indicator .completed {
    color: green;
    border-bottom-color: green;
  }

  .container {
    width: 60%;
    margin: 0 auto;
  }
</style>

</head>
<body class="p-4">

<div class="container">
  <h2 class="mb-4">Create Your Event Application</h2>

  <?php if (isset($success) && $success): ?>
    <div class="alert alert-success">Event application submitted successfully!</div>
  <?php endif; ?>

  <!-- Fiverr-style step indicator -->
  <div class="step-indicator mb-4">
    <div class="step-tab active" data-step="0">Basic</div>
    <div class="step-tab" data-step="1">Proposal</div>
    <div class="step-tab" data-step="2">Gallery</div>
    <div class="step-tab" data-step="3">Publish</div>
  </div>

  <form id="multiStepForm" method="POST" enctype="multipart/form-data">
    <!-- Step 1 -->
    <div class="step active">
      <h4>Step 1: Basic</h4>
      <div class="mb-3">
        <label>Title</label>
        <input name="title" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control" required></textarea>
      </div>
      <button type="button" class="btn btn-primary next">Next</button>
    </div>

    <!-- Step 2 -->
    <div class="step">
      <h4>Step 2: Proposal</h4>
      <div class="mb-3">
        <label>Upload Proposal (PDF or DOCX)</label>
        <input type="file" name="proposal" class="form-control" accept=".pdf,.doc,.docx" required>
      </div>
      <button type="button" class="btn btn-secondary prev">Back</button>
      <button type="button" class="btn btn-primary next">Next</button>
    </div>

    <!-- Step 3 -->
    <div class="step">
      <h4>Step 3: Gallery</h4>
      <div class="mb-3">
        <label>Flyer 1</label>
        <input type="file" name="image0" class="form-control" accept="image/*">
      </div>
      <div class="mb-3">
        <label>Flyer 2</label>
        <input type="file" name="image1" class="form-control" accept="image/*">
      </div>
      <div class="mb-3">
        <label>Flyer 3</label>
        <input type="file" name="image2" class="form-control" accept="image/*">
      </div>
      <button type="button" class="btn btn-secondary prev">Back</button>
      <button type="button" class="btn btn-primary next">Next</button>
    </div>

    <!-- Step 4 -->
    <div class="step">
      <h4>Step 4: Publish</h4>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="agree" required>
        <label class="form-check-label">I agree to the terms and conditions.</label>
      </div>
      <button type="button" class="btn btn-secondary prev">Back</button>
      <button type="submit" class="btn btn-success">Apply for Event</button>
    </div>
  </form>
</div>

<script>
  const steps = document.querySelectorAll(".step");
  const stepTabs = document.querySelectorAll(".step-tab");
  let currentStep = 0;

  function showStep(index) {
    steps.forEach((step, i) => {
      step.classList.toggle("active", i === index);
      stepTabs[i].classList.toggle("active", i === index);
      stepTabs[i].classList.toggle("completed", i < index);
    });
    currentStep = index;
  }

  document.querySelectorAll(".next").forEach(btn => {
    btn.onclick = () => {
      if (currentStep < steps.length - 1) {
        showStep(currentStep + 1);
      }
    };
  });

  document.querySelectorAll(".prev").forEach(btn => {
    btn.onclick = () => {
      if (currentStep > 0) {
        showStep(currentStep - 1);
      }
    };
  });

  stepTabs.forEach(tab => {
    tab.onclick = () => {
      const stepTo = parseInt(tab.dataset.step);
      if (stepTo <= currentStep) {
        showStep(stepTo);
      }
    };
  });
</script>

</body>
</html>
