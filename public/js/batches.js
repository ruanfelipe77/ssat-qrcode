$(document).ready(function () {
  // Inicialização do DataTable
  if ($.fn.DataTable.isDataTable("#batches-table")) {
    $("#batches-table").DataTable().destroy();
  }

  var table = $("#batches-table").DataTable({
    destroy: true,
    pagingType: "full_numbers",
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    responsive: true,
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "excel",
        className: "btn btn-success",
        text: '<i class="fas fa-file-excel me-2"></i>Excel',
      },
      {
        extend: "pdf",
        className: "btn btn-danger",
        text: '<i class="fas fa-file-pdf me-2"></i>PDF',
      },
      {
        extend: "print",
        className: "btn btn-info",
        text: '<i class="fas fa-print me-2"></i>Imprimir',
      },
    ],
    order: [[0, "desc"]], // Ordenar por Lote (mais recente primeiro)
  });

  // Preview do range de números de série
  function updateSerialPreview() {
    const start = parseInt($("#serial_start").val()) || 0;
    const quantity = parseInt($("#quantity").val()) || 0;

    if (start && quantity) {
      const end = start + quantity - 1;
      $("#serial_preview").val(`${start} até ${end}`);
    } else {
      $("#serial_preview").val("");
    }
  }

  $("#serial_start, #quantity").on("input", updateSerialPreview);

  // Novo Lote
  $("#add-batch").on("click", function () {
    // Atualizar preview do número do lote
    updateBatchPreview();
    const modal = new bootstrap.Modal(document.getElementById("batchModal"));
    modal.show();
  });

  // Função para atualizar o preview do número do lote
  function updateBatchPreview() {
    const currentMonthYear =
      new Date().toISOString().substr(5, 2) + new Date().getFullYear();

    // Buscar o próximo número disponível
    $.ajax({
      type: "GET",
      url: "src/controllers/BatchController.php",
      data: {
        action: "get_next_batch_number",
      },
      success: function (response) {
        try {
          const res = JSON.parse(response);
          if (res.success) {
            $("#batch-preview").text(res.next_number);
          } else {
            $("#batch-preview").text(currentMonthYear + "/0001");
          }
        } catch (e) {
          $("#batch-preview").text(currentMonthYear + "/0001");
        }
      },
      error: function () {
        $("#batch-preview").text(currentMonthYear + "/0001");
      },
    });
  }

  // Submissão do formulário de lote
  $("#batchForm").on("submit", function (event) {
    event.preventDefault();

    if (!this.checkValidity()) {
      event.stopPropagation();
      $(this).addClass("was-validated");
      return;
    }

    const formData = $(this).serialize();
    const quantity = parseInt($("#quantity").val());

    Swal.fire({
      title: "Confirmação",
      html: `
                Você está prestes a criar um lote com:<br>
                <b>${quantity}</b> produtos<br>
                Números de série: <b>${$("#serial_preview").val()}</b><br><br>
                Deseja continuar?
            `,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sim, criar lote",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: "POST",
          url: "src/controllers/BatchController.php",
          data: formData,
          success: function (response) {
            const res = JSON.parse(response);
            if (res.success) {
              const modal = bootstrap.Modal.getInstance(
                document.getElementById("batchModal")
              );
              modal.hide();

              Swal.fire({
                title: "Sucesso!",
                text: `Lote ${res.batch_number} criado com sucesso!`,
                icon: "success",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: "Erro!",
                text: res.message || "Erro ao criar lote",
                icon: "error",
              });
            }
          },
          error: function () {
            Swal.fire({
              title: "Erro!",
              text: "Erro ao criar lote",
              icon: "error",
            });
          },
        });
      }
    });
  });

  // Ver produtos do lote (usando delegação de eventos)
  $(document).on("click", ".view-products", function () {
    const id = $(this).data("id");
    const batchNumber = $(this).data("batch");

    // Atualizar informações do lote no modal
    $(".batch-number").text(batchNumber);

    // Carregar produtos
    $.ajax({
      type: "GET",
      url: "src/controllers/BatchController.php",
      data: {
        id: id,
        products: true,
      },
      success: function (response) {
        const products = JSON.parse(response);
        let html = "";

        if (products.length > 0) {
          $(".total-products").text(products.length);
          $(".available-products").text(
            products.filter((p) => !p.production_order_id).length
          );

          products.forEach((product) => {
            const isAvailable = !product.production_order_id;
            html += `
                            <div class="product-card p-3 mb-3 border rounded">
                                <div class="d-flex align-items-center">
                                    <div class="product-icon me-3">
                                        <i class="fas fa-box ${
                                          isAvailable
                                            ? "text-success"
                                            : "text-secondary"
                                        }"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">${
                                                  product.tipo_name
                                                }</h6>
                                                <p class="mb-0 text-muted small">
                                                    <span class="me-3">
                                                        <i class="fas fa-barcode me-1"></i>
                                                        ${product.serial_number}
                                                    </span>
                                                    <span>
                                                        <i class="fas fa-shield-alt me-1"></i>
                                                        ${product.warranty}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="d-flex gap-2">
                                                ${
                                                  isAvailable
                                                    ? `
                                                    <div class="form-check">
                                                        <input class="form-check-input product-select" 
                                                               type="checkbox" 
                                                               value="${product.id}"
                                                               id="product-${product.id}">
                                                    </div>
                                                `
                                                    : `
                                                    <span class="badge bg-secondary">Vendido</span>
                                                `
                                                }
                                                <button class="btn btn-sm btn-outline-primary print-qrcode" 
                                                        data-id="${product.id}" 
                                                        title="Imprimir QR Code">
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
                            <p class="mb-0">Este lote ainda não possui produtos cadastrados.</p>
                        </div>
                    `;
        }

        $(".products-list").html(html);

        // Mostrar botão de impressão em lote se houver produtos
        if (products.length > 0) {
          $(".print-selected").removeClass("d-none");
        } else {
          $(".print-selected").addClass("d-none");
        }

        // Inicializar o modal
        const modal = new bootstrap.Modal(
          document.getElementById("productsModal")
        );
        modal.show();
      },
    });
  });

  // Imprimir QR codes selecionados (usando delegação de eventos)
  $(document).on("click", ".print-selected", function () {
    const selectedProducts = [];
    $(".product-select:checked").each(function () {
      selectedProducts.push($(this).val());
    });

    if (selectedProducts.length === 0) {
      Swal.fire({
        title: "Atenção!",
        text: "Selecione pelo menos um produto para imprimir",
        icon: "warning",
      });
      return;
    }

    // Aqui você pode implementar a impressão em lote
    Swal.fire({
      title: "Impressão em Lote",
      text: `Imprimindo ${selectedProducts.length} QR codes...`,
      icon: "info",
    });
  });

  // Imprimir todos os QR codes do lote (usando delegação de eventos)
  $(document).on("click", ".print-all", function () {
    const id = $(this).data("id");

    Swal.fire({
      title: "Impressão em Lote",
      text: "Deseja imprimir todos os QR codes deste lote?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sim, imprimir todos",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        // Implementar impressão em lote
        Swal.fire({
          title: "Impressão iniciada!",
          text: "Os QR codes estão sendo gerados...",
          icon: "success",
        });
      }
    });
  });

  // Ver observações (usando delegação de eventos)
  $(document).on("click", ".view-notes", function () {
    const notes = $(this).data("notes");
    $(".notes-content").text(notes || "Nenhuma observação registrada.");

    const modal = new bootstrap.Modal(document.getElementById("notesModal"));
    modal.show();
  });

  // Excluir lote completo (usando delegação de eventos)
  $(document).on("click", ".delete-batch", function () {
    const id = $(this).data("id");
    const batchNumber = $(this).data("batch");
    const totalProducts = $(this).data("products");

    Swal.fire({
      title: "⚠️ Atenção!",
      html: `
                <div class="text-start">
                    <p>Você está prestes a excluir permanentemente:</p>
                    <ul class="list-unstyled ms-3">
                        <li><strong>📦 Lote:</strong> ${batchNumber}</li>
                        <li><strong>📊 Produtos:</strong> ${totalProducts} itens</li>
                    </ul>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Esta ação é irreversível!</strong><br>
                        Todos os produtos e QR codes serão excluídos permanentemente.
                    </div>
                    <p class="mb-0">Deseja continuar?</p>
                </div>
            `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText:
        '<i class="fas fa-trash-alt me-2"></i>Sim, excluir lote',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
      reverseButtons: true,
      customClass: {
        popup: "swal-wide",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: "Excluindo lote...",
          html: "Por favor, aguarde...",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        $.ajax({
          type: "POST",
          url: "src/controllers/BatchController.php",
          data: {
            action: "delete_batch",
            id: id,
          },
          success: function (response) {
            const res = JSON.parse(response);
            if (res.success) {
              Swal.fire({
                title: "✅ Sucesso!",
                html: `
                                    <div class="text-center">
                                        <p class="mb-3">Lote <strong>${batchNumber}</strong> excluído com sucesso!</p>
                                        <div class="d-flex justify-content-center gap-3">
                                            <span class="badge bg-success fs-6">
                                                <i class="fas fa-check me-1"></i>
                                                ${
                                                  res.deleted_products ||
                                                  totalProducts
                                                } produtos excluídos
                                            </span>
                                            <span class="badge bg-info fs-6">
                                                <i class="fas fa-qrcode me-1"></i>
                                                QR codes removidos
                                            </span>
                                        </div>
                                    </div>
                                `,
                icon: "success",
                confirmButtonText: "OK",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: "❌ Erro!",
                text: res.message || "Erro ao excluir lote",
                icon: "error",
              });
            }
          },
          error: function (xhr, status, error) {
            Swal.fire({
              title: "❌ Erro!",
              html: `
                            <p>Erro ao excluir lote:</p>
                            <code>${error}</code>
                        `,
              icon: "error",
            });
          },
        });
      }
    });
  });
});
