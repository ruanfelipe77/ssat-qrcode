$(document).ready(function () {
  // Run only on Users page
  if (!document.getElementById('users-table')) return;

  // Initialize DataTable
  if ($.fn.DataTable.isDataTable('#users-table')) {
    $('#users-table').DataTable().destroy();
  }
  const table = $('#users-table').DataTable({
    destroy: true,
    pagingType: 'full_numbers',
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, 'Todos'],
    ],
    responsive: true,
    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json' },
    dom: 'frtip',
    order: [[0, 'desc']],
  });

  function resetUserForm() {
    const form = $('#userForm');
    form.removeClass('was-validated')[0].reset();
    $('#user_action').val('add');
    $('#user_id').val('');
    $('.user-modal-title-text').text('Novo Usuário');
    $('.btn-acao').html('<i class="fas fa-save me-2"></i>Salvar');
    // Password required on add
    $('#password').attr('required', true);
    $('#passwordHelp').text('(obrigatória)');
    $('#active').prop('checked', true);
  }

  // Add User
  $('#add-user').on('click', function () {
    resetUserForm();
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
  });

  // Edit User
  $(document).on('click', '.edit-user', function () {
    const id = $(this).data('id');
    $.get('src/controllers/UserController.php', { id }, function (res) {
      const u = typeof res === 'string' ? JSON.parse(res) : res;
      resetUserForm();
      $('#user_action').val('edit');
      $('#user_id').val(u.id);
      $('#name').val(u.name);
      $('#email').val(u.email);
      $('#active').prop('checked', Number(u.active) === 1);
      // Password optional on edit
      $('#password').val('').removeAttr('required');
      $('#passwordHelp').text('(opcional)');
      $('.user-modal-title-text').text('Editar Usuário');
      $('.btn-acao').html('<i class="fas fa-save me-2"></i>Atualizar');
      const modal = new bootstrap.Modal(document.getElementById('userModal'));
      modal.show();
    });
  });

  // Toggle active
  $(document).on('click', '.toggle-user', function () {
    const id = $(this).data('id');
    const active = $(this).data('active');
    $.post('src/controllers/UserController.php', { action: 'toggle_active', id, active }, function (res) {
      const r = typeof res === 'string' ? JSON.parse(res) : res;
      if (r.success) {
        location.reload();
      } else {
        Swal.fire({ title: 'Erro', text: 'Falha ao atualizar status', icon: 'error' });
      }
    });
  });

  // Delete user
  $(document).on('click', '.delete-user', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Excluir usuário?',
      text: 'Esta ação não poderá ser desfeita.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fas fa-trash me-2"></i>Excluir',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('src/controllers/UserController.php', { action: 'delete', id }, function (res) {
          const r = typeof res === 'string' ? JSON.parse(res) : res;
          if (r.success) {
            Swal.fire({ title: 'Excluído', text: 'Usuário removido com sucesso', icon: 'success' }).then(() => location.reload());
          } else {
            Swal.fire({ title: 'Erro', text: r.message || 'Falha ao excluir', icon: 'error' });
          }
        });
      }
    });
  });

  // Submit form (add/edit)
  $('#userForm').on('submit', function (e) {
    e.preventDefault();
    const form = this;
    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add('was-validated');
      return;
    }
    const data = $(this).serializeArray();
    // Normalize active checkbox to 1/0
    const activeChecked = $('#active').is(':checked') ? 1 : 0;
    const action = $('#user_action').val();
    const payload = $(this).serialize() + `&action=${encodeURIComponent(action)}&active=${activeChecked}`;

    $.post('src/controllers/UserController.php', payload, function (res) {
      const r = typeof res === 'string' ? JSON.parse(res) : res;
      if (r && r.success) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
        if (modal) modal.hide();
        Swal.fire({ title: 'Sucesso', text: action === 'add' ? 'Usuário criado' : 'Usuário atualizado', icon: 'success' }).then(() => location.reload());
      } else {
        Swal.fire({ title: 'Erro', text: (r && r.message) ? r.message : 'Falha ao salvar usuário', icon: 'error' });
      }
    });
  });
});
