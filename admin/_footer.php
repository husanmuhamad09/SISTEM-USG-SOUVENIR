</div>
  </div>

  <!-- Pop-up Konfirmasi Log Out sesuai Wireframe -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-body p-4 text-center">
          <h2 class="h6 fw-bold mb-3" id="logoutModalLabel">Apakah anda yakin ingin log out?</h2>
          <div class="d-flex justify-content-center gap-2">
            <form method="post" action="logout.php" class="m-0">
              <input type="hidden" name="confirm_logout" value="1">
              <button type="submit" class="btn btn-dark px-4 rounded-3 fw-semibold">Ya</button>
            </form>
            <button type="button" class="btn btn-outline-secondary px-4 rounded-3 fw-semibold" data-bs-dismiss="modal">
              Tidak
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>