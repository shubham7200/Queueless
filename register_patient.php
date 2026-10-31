
<?php
session_start();
include 'connection.php';

// Allow only receptionists
if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'receptionist'
) {
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Patient Registration | QueueLess</title>

    <link rel="stylesheet" href="css/register_patient.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">
</head>

<body>

<!-- Header -->
<header class="registration-header">
    <a href="receptionist_dashboard.php" class="brand">
        <span class="brand-icon">✚</span>
        <span>Queue<span class="brand-highlight">Less</span></span>
    </a>

    <div class="header-right">
        <span class="workspace-label">Reception Workspace</span>
        <a href="receptionist_dashboard.php" class="back-link">
            <span>←</span> Dashboard
        </a>
    </div>
</header>

<main class="page-wrapper">

    <!-- Page heading -->
    <section class="page-heading">
        <div class="heading-icon">＋</div>

        <div>
            <span class="eyebrow">PATIENT MANAGEMENT</span>
            <h1>Patient Registration</h1>
            <p>Register a new patient or find an existing patient record.</p>
        </div>
    </section>

    <!-- Existing patient search -->
    <section class="search-card">

        <div class="section-title">
            <div class="section-icon search-icon">⌕</div>
            <div>
                <h2>Search Existing Patient</h2>
                <p>Search by mobile number to retrieve saved patient details.</p>
            </div>
        </div>

        <label for="search_mobile">Mobile Number</label>

        <div class="search-row">
            <input
                type="tel"
                id="search_mobile"
                maxlength="10"
                pattern="[0-9]{10}"
                inputmode="numeric"
                placeholder="Enter 10-digit mobile number"
                autocomplete="off"
            >

            <button type="button" id="searchBtn" class="search-button">
                <span>⌕</span> Search Patient
            </button>
        </div>

        <div id="searchMessage" class="message" role="status"
             aria-live="polite"></div>

        <p class="search-note">
            <span>ⓘ</span>
            Existing patient details will be filled automatically.
            Only the department and doctor can be changed.
        </p>

    </section>

    <!-- Registration form -->
    <form action="register_p.php" method="POST" id="patientForm"
          class="registration-form">

        <input type="hidden" name="existing_patient"
               id="existing_patient" value="0">

        <!-- Patient details -->
        <section class="form-section">

            <div class="section-title">
                <div class="section-icon patient-icon">♙</div>
                <div>
                    <h2>Patient Details</h2>
                    <p>Enter the patient's personal information.</p>
                </div>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label for="fname">First Name <span>*</span></label>
                    <input type="text" name="fname" id="fname"
                           placeholder="Enter first name" maxlength="100"
                           autocomplete="given-name" required>
                </div>

                <div class="form-group">
                    <label for="mname">Middle Name</label>
                    <input type="text" name="mname" id="mname"
                           placeholder="Enter middle name" maxlength="100">
                </div>

                <div class="form-group">
                    <label for="lname">Last Name <span>*</span></label>
                    <input type="text" name="lname" id="lname"
                           placeholder="Enter last name" maxlength="100"
                           autocomplete="family-name" required>
                </div>

                <div class="form-group">
                    <label for="age">Age <span>*</span></label>
                    <input type="number" name="age" id="age"
                           placeholder="Enter age" min="0" max="130" required>
                </div>

                <div class="form-group full-width">
                    <label>Gender <span>*</span></label>

                    <div class="radio-options">
                        <label class="radio-option">
                            <input type="radio" name="gender" value="Male" required>
                            <span>Male</span>
                        </label>

                        <label class="radio-option">
                            <input type="radio" name="gender" value="Female">
                            <span>Female</span>
                        </label>

                        <label class="radio-option">
                            <input type="radio" name="gender" value="Other">
                            <span>Other</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mobile_number">
                        Mobile Number <span>*</span>
                    </label>

                    <input type="tel" name="mobile_number" id="mobile_number"
                           placeholder="10-digit mobile number"
                           maxlength="10" pattern="[0-9]{10}"
                           inputmode="numeric" autocomplete="tel" required>
                </div>

                <div class="form-group">
                    <label for="blood_group">Blood Group <span>*</span></label>

                    <select name="blood_group" id="blood_group" required>
                        <option value="">Select blood group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="Unknown">Unknown</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="patient_type">Type of Patient <span>*</span></label>

                    <select name="patient_type" id="patient_type" required>
                        <option value="">Select patient type</option>
                        <option value="Regular">Regular</option>
                        <option value="First time">First time</option>
                    </select>
                </div>

            </div>

        </section>

        <!-- Appointment details -->
        <section class="form-section appointment-section">

            <div class="section-title">
                <div class="section-icon appointment-icon">▤</div>
                <div>
                    <h2>Appointment Details</h2>
                    <p>Select the department and doctor for this visit.</p>
                </div>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label for="department">Department <span>*</span></label>

                    <select name="department" id="department" required>
                        <option value="">Select department</option>
                        <option value="General Medicine">General Medicine</option>
                        <option value="Orthopaedics">Orthopaedics</option>
                        <option value="Paediatrics">Paediatrics</option>
                        <option value="Cardiology">Cardiology</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="doctor_id">Select Doctor <span>*</span></label>

                    <select name="doctor_id" id="doctor_id" required disabled>
                        <option value="">Select department first</option>
                    </select>
                </div>

            </div>

            <div id="doctorMessage" class="message" aria-live="polite"></div>

        </section>

        <!-- Form buttons -->
        <div class="form-actions">
            <button type="reset" class="reset-button">Clear Form</button>

            <button type="submit" name="submit" class="submit-button">
                Register Patient <span>→</span>
            </button>
        </div>

        <p class="required-note">
            Fields marked <span>*</span> are required.
        </p>

    </form>

    <footer class="page-footer">
        <span>© <?php echo date('Y'); ?> QueueLess</span>
        <span>Making hospital visits simpler.</span>
    </footer>

</main>

<script>
const department = document.getElementById("department");
const doctor = document.getElementById("doctor_id");
const searchMessage = document.getElementById("searchMessage");
const doctorMessage = document.getElementById("doctorMessage");
const existingPatient = document.getElementById("existing_patient");
const searchButton = document.getElementById("searchBtn");
const searchMobile = document.getElementById("search_mobile");
const patientForm = document.getElementById("patientForm");

const personalFields = [
    "fname",
    "mname",
    "lname",
    "age",
    "mobile_number",
    "blood_group",
    "patient_type"
];

let searchRequestNumber = 0;
let doctorRequestNumber = 0;

function setMessage(element, message, type = "") {
    element.textContent = message;
    element.className = "message";

    if (type) {
        element.classList.add(type);
    }
}

function setGender(value) {
    document.querySelectorAll('input[name="gender"]').forEach(function(radio) {
        radio.checked = radio.value === value;
        radio.disabled = existingPatient.value === "1";
    });
}

function clearPatientDetails() {
    existingPatient.value = "0";

    personalFields.forEach(function(id) {
        const field = document.getElementById(id);

        field.value = "";
        field.readOnly = false;
        field.disabled = false;
        field.classList.remove("locked");
    });

    setGender("");

    department.value = "";
    doctor.innerHTML = '<option value="">Select department first</option>';
    doctor.disabled = true;

    doctorRequestNumber++;

    setMessage(doctorMessage, "");
}

async function searchPatient() {
    const mobile = searchMobile.value.trim();

    setMessage(searchMessage, "");

    if (!/^[0-9]{10}$/.test(mobile)) {
        setMessage(
            searchMessage,
            "Please enter a valid 10-digit mobile number.",
            "error"
        );
        return;
    }

    const requestNumber = ++searchRequestNumber;

    searchButton.disabled = true;
    searchButton.textContent = "Searching...";

    try {
        const response = await fetch(
            "search_patient.php?mobile_number=" + encodeURIComponent(mobile),
            {
                method: "GET",
                credentials: "same-origin",
                headers: { "Accept": "application/json" }
            }
        );

        const responseText = await response.text();

        if (requestNumber !== searchRequestNumber) return;

        let data;

        try {
            data = JSON.parse(responseText);
        } catch (error) {
            console.error("Invalid search response:", responseText);
            throw new Error("Invalid server response. Check search_patient.php.");
        }

        if (!response.ok) {
            throw new Error(data.message || "HTTP " + response.status);
        }

        clearPatientDetails();

        if (data.success && data.patient) {
            const p = data.patient;

            document.getElementById("fname").value = p.fname || "";
            document.getElementById("mname").value = p.mname || "";
            document.getElementById("lname").value = p.lname || "";
            document.getElementById("age").value = p.age ?? "";
            document.getElementById("mobile_number").value =
                p.mobile_number || mobile;
            document.getElementById("blood_group").value =
                p.blood_group || "";
            document.getElementById("patient_type").value =
                p.patient_type || "";

            existingPatient.value = "1";
            setGender(p.gender || "");

            personalFields.forEach(function(id) {
                const field = document.getElementById(id);

                field.classList.add("locked");

                if (field.tagName === "SELECT") {
                    field.disabled = true;
                } else {
                    field.readOnly = true;
                }
            });

            setMessage(
                searchMessage,
                "Existing patient found. Personal details are locked. Select a department and doctor.",
                "success"
            );

        } else {
            document.getElementById("mobile_number").value = mobile;

            setMessage(
                searchMessage,
                (data.message || "Patient not found.") +
                " Enter the details below to register a new patient.",
                "error"
            );
        }

    } catch (error) {
        console.error("Patient search error:", error);

        setMessage(searchMessage, "Search failed: " + error.message, "error");

    } finally {
        if (requestNumber === searchRequestNumber) {
            searchButton.disabled = false;
            searchButton.textContent = "⌕ Search Patient";
        }
    }
}

searchButton.addEventListener("click", searchPatient);

searchMobile.addEventListener("input", function() {
    searchRequestNumber++;
    clearPatientDetails();

    setMessage(searchMessage, "");

    searchButton.disabled = false;
    searchButton.textContent = "⌕ Search Patient";
});

// Load doctors for the selected department
department.addEventListener("change", async function() {
    const selectedDepartment = this.value;
    const requestNumber = ++doctorRequestNumber;

    doctor.innerHTML = '<option value="">Loading doctors...</option>';
    doctor.disabled = true;
    setMessage(doctorMessage, "");

    if (!selectedDepartment) {
        doctor.innerHTML = '<option value="">Select department first</option>';
        return;
    }

    try {
        const response = await fetch(
            "get_doctors.php?department=" +
            encodeURIComponent(selectedDepartment),
            {
                credentials: "same-origin",
                headers: { "Accept": "application/json" }
            }
        );

        const responseText = await response.text();

        let doctors;

        try {
            doctors = JSON.parse(responseText);
        } catch (error) {
            console.error("Doctor response:", responseText);
            throw new Error("Invalid doctor response.");
        }

        if (!response.ok) {
            throw new Error("HTTP " + response.status);
        }

        if (requestNumber !== doctorRequestNumber) return;

        doctor.innerHTML = '<option value="">Select doctor</option>';

        if (!Array.isArray(doctors) || doctors.length === 0) {
            doctor.innerHTML = '<option value="">No active doctors found</option>';

            setMessage(
                doctorMessage,
                "No active doctors found in this department.",
                "error"
            );
            return;
        }

        doctors.forEach(function(item) {
            const option = document.createElement("option");
            option.value = item.id;
            option.textContent = "Dr. " + item.fname + " " + item.lname;
            doctor.appendChild(option);
        });

        doctor.disabled = false;

    } catch (error) {
        if (requestNumber !== doctorRequestNumber) return;

        console.error("Doctor loading error:", error);
        doctor.innerHTML = '<option value="">Error loading doctors</option>';

        setMessage(
            doctorMessage,
            "Unable to load doctors: " + error.message,
            "error"
        );
    }
});

// Reset all registration state
patientForm.addEventListener("reset", function() {
    searchRequestNumber++;
    doctorRequestNumber++;

    setTimeout(function() {
        clearPatientDetails();
        searchMobile.value = "";

        setMessage(searchMessage, "");
        setMessage(doctorMessage, "");

        searchButton.disabled = false;
        searchButton.textContent = "⌕ Search Patient";
    }, 0);
});
</script>

</body>
</html>
