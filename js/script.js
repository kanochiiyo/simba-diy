document.addEventListener("DOMContentLoaded", function () {
  // Toggle Password for Login/Register
  const togglePassword = document.getElementById("togglePassword");
  const password = document.getElementById("password");

  const toggleConfirmPassword = document.getElementById(
    "toggleConfirmPassword"
  );
  const confirmpassword = document.getElementById("confirmpassword");

  // Toggle untuk password utama
  if (togglePassword && password) {
    togglePassword.addEventListener("click", function () {
      const type =
        password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);
      this.classList.toggle("fa-eye");
      this.classList.toggle("fa-eye-slash");
    });
  }

  // Toggle untuk confirm password
  if (toggleConfirmPassword && confirmpassword) {
    toggleConfirmPassword.addEventListener("click", function () {
      const type =
        confirmpassword.getAttribute("type") === "password"
          ? "text"
          : "password";
      confirmpassword.setAttribute("type", type);
      this.classList.toggle("fa-eye");
      this.classList.toggle("fa-eye-slash");
    });
  }

  // FAQ Accordion
  const faqQuestions = document.querySelectorAll(".faq-question");

  faqQuestions.forEach((button) => {
    button.addEventListener("click", function () {
      const faqItem = this.parentElement;
      const answer = faqItem.querySelector(".faq-answer");
      const isActive = this.classList.contains("active");

      // Close all FAQ items
      faqQuestions.forEach((btn) => {
        btn.classList.remove("active");
        const ans = btn.parentElement.querySelector(".faq-answer");
        if (ans) {
          ans.style.maxHeight = null;
        }
      });

      // Toggle current item
      if (!isActive) {
        this.classList.add("active");
        answer.style.maxHeight = answer.scrollHeight + "px";
      }
    });
  });

  // Smooth scrolling for navigation links
  const navLinks = document.querySelectorAll('a[href^="#"]');

  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      // Only smooth scroll for hash links, not empty hashes
      if (href && href !== "#") {
        const target = document.querySelector(href);

        if (target) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }
      }
    });
  });
});
