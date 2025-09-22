<?php ?>
<!-- Modal Usuário -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">
          <i class="fas fa-user me-2"></i>
          <span class="user-modal-title-text">Novo Usuário</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="userForm" class="needs-validation" novalidate>
          <input type="hidden" name="action" id="user_action" value="add">
          <input type="hidden" name="id" id="user_id" value="">

          <div class="mb-3">
            <label for="name" class="form-label">Nome</label>
            <input type="text" class="form-control" id="name" name="name" required>
            <div class="invalid-feedback">Informe o nome.</div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="invalid-feedback">Informe um e-mail válido.</div>
          </div>

          <div class="mb-3" id="passwordGroup">
            <label for="password" class="form-label">Senha <small class="text-muted" id="passwordHelp">(obrigatória)</small></label>
            <input type="password" class="form-control" id="password" name="password" minlength="6">
            <div class="invalid-feedback">A senha deve ter ao menos 6 caracteres.</div>
          </div>

          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="active" name="active" checked>
            <label class="form-check-label" for="active">Ativo</label>
          </div>

          <div class="modal-footer px-0 pb-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-2"></i>Cancelar
            </button>
            <button type="submit" class="btn btn-primary btn-acao">
              <i class="fas fa-save me-2"></i>Salvar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userForm');
    if (form) {
      form.addEventListener('submit', function(e){
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      });
    }
  });
  </script>
</div>
