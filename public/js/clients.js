$(document).ready(function () {
  // Inicialização do DataTable
  var table = $("#clients-table").DataTable({
    pagingType: "full_numbers",
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    responsive: true,
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
    },
    dom: "frtip",
    initComplete: function () {
      console.log("DataTable inicializado");
      console.log("Total de linhas:", this.api().rows().count());
      $("#clients-table").addClass("initialized");
      $(".table-loading-static").remove();
    },
  });

  // Inicializar tooltips
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Função para limpar e resetar o formulário
  function resetForm() {
    const form = $("#clientForm");
    form.removeClass("was-validated");
    form[0].reset();
    $("#action").val("");
    $("#id").val("");
  }

  // Manipulador para visualizar produtos (usando event delegation)
  $(document).on("click", ".view-products", function () {
    const clientId = $(this).data("id");
    const clientName = $(this).data("name");
    const clientLocation = $(this).data("location");

    // Atualizar informações do cliente no modal
    $(".client-name").text(clientName);
    if ($(".client-location span").length > 0) {
      $(".client-location span").text(clientLocation);
    } else {
      $(".client-location").text(clientLocation);
    }

    // Carregar produtos do cliente
    $.ajax({
      type: "GET",
      url: "src/controllers/ProductController.php",
      data: { client_id: clientId },
      success: function (response) {
        try {
          const products =
            typeof response === "string" ? JSON.parse(response) : response;
          let html = "";

          if (products.length > 0) {
            products.forEach((product) => {
              const saleDate = product.sale_date
                ? new Date(product.sale_date).toLocaleDateString("pt-BR")
                : "Não informado";

              html += `
                            <div class="product-card p-3 mb-3 border rounded">
                                <div class="d-flex align-items-center">
                                    <div class="product-icon me-3">
                                        <i class="fas fa-box text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">${product.tipo_name || "Sem Tipo"}</h6>
                                                <p class="mb-0 text-muted small">
                                                    <span class="me-3">
                                                        <i class="fas fa-barcode me-1"></i>
                                                        ${product.serial_number || "N/A"}
                                                    </span>
                                                    <span class="me-3">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        ${saleDate}
                                                    </span>
                                                    <span>
                                                        <i class="fas fa-shield-alt me-1"></i>
                                                        ${product.warranty || "N/A"}
                                                    </span>
                                                </p>
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary print-qrcode" data-id="${product.id}" title="Imprimir QR Code">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
            });
          } else {
            html = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-box-open mb-3" style="font-size: 3rem;"></i>
                            <h5>Nenhum produto encontrado</h5>
                            <p class="mb-0">Este cliente ainda não possui produtos cadastrados.</p>
                        </div>
                    `;
          }

          $(".products-list").html(html);

          const el = document.getElementById("productsModal");
          const modal = bootstrap.Modal.getOrCreateInstance(el);
          modal.show();
        } catch (e) {
          console.error("Erro ao processar produtos:", response, e);
          $(".products-list").html(`
            <div class="text-center text-danger py-5">
              <i class="fas fa-exclamation-triangle mb-3" style="font-size: 3rem;"></i>
              <h5>Erro ao carregar produtos</h5>
              <p class="mb-0">Não foi possível carregar os produtos deste cliente.</p>
            </div>
          `);
        }
      },
      error: function () {
        Swal.fire({
          title: "Erro!",
          text: "Houve um problema ao carregar os produtos.",
          icon: "error",
        });
      },
    });
  });

  // Imprimir QR individual (abrir em nova aba)
  $(document).on("click", ".print-qrcode", function () {
    const id = $(this).data("id");
    if (!id) return;
    window.open(
      `src/controllers/QrController.php?id=${encodeURIComponent(id)}&s=300`,
      "_blank",
    );
  });

  // Manipulador para adicionar novo cliente
  $("#add-client").on("click", function () {
    resetForm();
    $("#action").val("add");
    $(".modal-title-text").text("Adicionar Cliente");
    $(".btn-acao").html('<i class="fas fa-save me-2"></i>Salvar');
    const modal = new bootstrap.Modal(document.getElementById("clientModal"));
    modal.show();
  });

  // Manipulador para editar cliente (usando event delegation)
  $(document).on("click", ".edit-client", function () {
    const id = $(this).data("id");

    $.ajax({
      type: "GET",
      url: "src/controllers/ClientController.php",
      data: { id: id },
      success: function (response) {
        const client = JSON.parse(response);
        resetForm();

        $(".modal-title-text").text("Editar Cliente");
        $(".btn-acao").html('<i class="fas fa-save me-2"></i>Atualizar');
        $("#action").val("edit");
        $("#id").val(client.id);
        $("#name").val(client.name);
        $("#city").val(client.city);
        $("#state").val(client.state);

        const modal = new bootstrap.Modal(
          document.getElementById("clientModal"),
        );
        modal.show();
      },
      error: function () {
        Swal.fire({
          title: "Erro!",
          text: "Houve um problema ao buscar os dados do cliente.",
          icon: "error",
        });
      },
    });
  });

  // Manipulador para submissão do formulário
  $("#clientForm").on("submit", function (event) {
    event.preventDefault();

    const form = $(this)[0];
    if (!form.checkValidity()) {
      event.stopPropagation();
      form.classList.add("was-validated");
      return;
    }

    const formData = $(this).serialize();
    const isEdit = $("#action").val() === "edit";

    $.ajax({
      type: "POST",
      url: "src/controllers/ClientController.php",
      data: formData,
      success: function (response) {
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("clientModal"),
        );
        modal.hide();

        Swal.fire({
          title: "Sucesso!",
          text: isEdit
            ? "Cliente atualizado com sucesso!"
            : "Cliente criado com sucesso!",
          icon: "success",
        }).then(() => {
          location.reload();
        });
      },
      error: function () {
        Swal.fire({
          title: "Erro!",
          text: "Houve um problema ao salvar o cliente.",
          icon: "error",
        });
      },
    });
  });

  // Manipulador para deletar cliente (usando event delegation)
  $(document).on("click", ".delete-client", function () {
    const id = $(this).data("id");

    Swal.fire({
      title: "Confirmar exclusão",
      text: "Esta ação não poderá ser revertida!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: '<i class="fas fa-trash me-2"></i>Sim, excluir!',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: "POST",
          url: "src/controllers/ClientController.php",
          data: {
            action: "delete",
            id: id,
          },
          success: function (response) {
            const res = JSON.parse(response);
            if (res.success) {
              Swal.fire({
                title: "Excluído!",
                text: "Cliente foi excluído com sucesso.",
                icon: "success",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: "Erro!",
                text: "Não é possível excluir um cliente que possui pedidos.",
                icon: "error",
              });
            }
          },
          error: function () {
            Swal.fire({
              title: "Erro!",
              text: "Houve um problema ao excluir o cliente.",
              icon: "error",
            });
          },
        });
      }
    });
  });

  // Fechar modal ao clicar no botão de fechar
  $('.btn-close, .btn[data-bs-dismiss="modal"]').on("click", function () {
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("clientModal"),
    );
    if (modal) {
      modal.hide();
    }
  });

  // Resetar formulário quando o modal for fechado
  $("#clientModal").on("hidden.bs.modal", function () {
    resetForm();
  });
});
