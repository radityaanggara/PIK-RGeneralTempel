/* ==========================================================
   form.js — Konseling Form Handler
   ========================================================== */

const form = document.querySelector("#konselingForm");

if (form) {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        // simple validation
        for (let value of formData.values()) {
            if (value.trim() === "") {
                alert("Semua field wajib diisi!");
                return;
            }
        }

        try {
            const response = await fetch("../backend/save_konseling.php", {
                method: "POST",
                body: formData
            });

            const result = await response.text();

            alert(result);

            form.reset();
        } catch (error) {
            alert("Gagal mengirim data. Coba lagi!");
        }
    });
}
