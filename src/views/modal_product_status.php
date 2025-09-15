<!-- Modal de Status de Produto -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">
                    <i class="fas fa-tags me-2"></i>
                    <span id="statusModalTitle">Novo Status</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="statusForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="create" id="action">
                    <input type="hidden" name="id" id="status_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nome do Status</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   placeholder="ex: em_producao">
                            <div class="form-text">Use snake_case (sublinhado ao invés de espaços)</div>
                            <div class="invalid-feedback">
                                Por favor, informe o nome do status.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">Cor</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" 
                                       id="color" name="color" value="#6c757d" required>
                                <input type="text" class="form-control" id="color_text" 
                                       placeholder="#6c757d" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                            <div class="invalid-feedback">
                                Por favor, selecione uma cor válida.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="icon" class="form-label">Ícone (Font Awesome)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i id="icon_preview" class="fas fa-circle"></i>
                                </span>
                                <input type="text" class="form-control" id="icon" name="icon" 
                                       value="fas fa-circle" required
                                       placeholder="ex: fas fa-check, fas fa-warehouse">
                            </div>
                            <div class="form-text">
                                <a href="https://fontawesome.com/icons" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    Ver ícones disponíveis
                                </a>
                            </div>
                            <div class="invalid-feedback">
                                Por favor, informe um ícone válido.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-select" id="is_active" name="is_active" required>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Descrição detalhada do status..."></textarea>
                    </div>

                    <!-- Preview do Status -->
                    <div class="mb-3">
                        <label class="form-label">Preview</label>
                        <div class="p-3 bg-light rounded">
                            <span class="badge d-flex align-items-center" id="status_preview" 
                                  style="background-color: #6c757d; width: fit-content;">
                                <i class="fas fa-circle me-2"></i>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="submit" form="statusForm" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    <span id="submitButtonText">Salvar Status</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Função para limpar e resetar o formulário
    function resetForm() {
        const form = $("#statusForm");
        form.removeClass("was-validated");
        form[0].reset();
        $("#action").val("create");
        $("#status_id").val("");
        $("#statusModalTitle").text("Novo Status");
        $("#submitButtonText").text("Salvar Status");
        $("#color").val("#6c757d");
        $("#color_text").val("#6c757d");
        $("#icon").val("fas fa-circle");
        updatePreview();
    }

    // Função para atualizar o preview
    function updatePreview() {
        const name = $("#name").val() || "";
        const color = $("#color").val();

        const preview = $("#status_preview");
        preview.css("background-color", color);
        // zera conteúdo e mostra apenas o nome formatado
        preview.html(
          $('<span>').text(name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))
        );
    }

    // Sincronizar color picker com input text
    $("#color").on("change", function() {
        $("#color_text").val($(this).val());
        updatePreview();
    });

    $("#color_text").on("input", function() {
        const value = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            $("#color").val(value);
            updatePreview();
        }
    });

    // Atualizar preview quando campos mudarem
    $("#name, #icon").on("input", updatePreview);

    // Novo Status
    $("#add-status").on("click", function () {
        resetForm();
        const modal = new bootstrap.Modal(document.getElementById("statusModal"));
        modal.show();
    });

    // Editar Status
    $(document).on("click", ".edit-status", function () {
        const id = $(this).data("id");
        
        $.ajax({
            type: "GET",
            url: "src/controllers/ProductStatusController.php",
            data: { id: id },
            success: function (response) {
                const status = JSON.parse(response);
                
                $("#action").val("edit");
                $("#status_id").val(status.id);
                $("#name").val(status.name);
                $("#description").val(status.description);
                $("#color").val(status.color);
                $("#color_text").val(status.color);
                $("#icon").val(status.icon);
                $("#is_active").val(status.is_active);
                
                $("#statusModalTitle").text("Editar Status");
                $("#submitButtonText").text("Atualizar Status");
                
                updatePreview();
                
                const modal = new bootstrap.Modal(document.getElementById("statusModal"));
                modal.show();
            }
        });
    });

    // Excluir Status
    $(document).on("click", ".delete-status", function () {
        const id = $(this).data("id");
        const name = $(this).data("name");

        Swal.fire({
            title: "Atenção!",
            html: `Deseja excluir o status <strong>${name}</strong>?<br><br>
                   <small class="text-muted">Esta ação não pode ser desfeita.</small>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Sim, excluir",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "src/controllers/ProductStatusController.php",
                    data: {
                        action: "delete",
                        id: id
                    },
                    success: function (response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire({
                                title: "Sucesso!",
                                text: "Status excluído com sucesso!",
                                icon: "success"
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: "Erro!",
                                text: res.message || "Erro ao excluir status",
                                icon: "error"
                            });
                        }
                    }
                });
            }
        });
    });

    // Submissão do formulário
    $("#statusForm").on("submit", function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass("was-validated");
            return;
        }

        const formData = $(this).serialize();

        $.ajax({
            type: "POST",
            url: "src/controllers/ProductStatusController.php",
            data: formData,
            success: function (response) {
                const res = JSON.parse(response);
                if (res.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById("statusModal"));
                    modal.hide();

                    Swal.fire({
                        title: "Sucesso!",
                        text: res.message || "Status salvo com sucesso!",
                        icon: "success"
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: "Erro!",
                        text: res.message || "Erro ao salvar status",
                        icon: "error"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: "Erro!",
                    text: "Erro ao salvar status",
                    icon: "error"
                });
            }
        });
    });

    // Inicializar preview
    updatePreview();
});
</script>
