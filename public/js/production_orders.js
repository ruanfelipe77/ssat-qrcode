$(document).ready(function () {
  // Inicialização do DataTable
  if ($.fn.DataTable.isDataTable("#orders-table")) {
    $("#orders-table").DataTable().destroy();
  }

  var table = $("#orders-table").DataTable({
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
    order: [[0, "desc"]], // Ordenar por PP (mais recente primeiro)
  });

  // Inicializar tooltips
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Novo Pedido
  $("#add-order").on("click", function () {
    const modal = new bootstrap.Modal(document.getElementById("orderModal"));
    modal.show();
  });

  // Atualizar status (usando delegação de eventos)
  $(document).on("change", ".status-select", function () {
    const id = $(this).data("id");
    const status = $(this).val();

    $.ajax({
      type: "POST",
      url: "src/controllers/ProductionOrderController.php",
      data: {
        action: "update_status",
        id: id,
        status: status,
      },
      success: function (response) {
        const res = JSON.parse(response);
        if (res.success) {
          Swal.fire({
            title: "Sucesso!",
            text: "Status atualizado com sucesso!",
            icon: "success",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
          });
        } else {
          Swal.fire({
            title: "Erro!",
            text: "Erro ao atualizar status",
            icon: "error",
          });
        }
      },
    });
  });

  // Ver produtos do pedido (usando delegação de eventos)
  $(document).on("click", ".view-products", function () {
    const id = $(this).data("id");
    const orderNumber = $(this).data("order");

    // Atualizar informações do Pedido no modal
    $(".order-number").text(orderNumber);

    // Carregar produtos
    $.ajax({
      type: "GET",
      url: "src/controllers/ProductionOrderController.php",
      data: {
        id: id,
        products: true,
      },
      success: function (response) {
        const products = JSON.parse(response);
        let html = "";

        if (products.length > 0) {
          $(".total-products").text(products.length);

          products.forEach((product) => {
            const saleDate = new Date(product.sale_date).toLocaleDateString(
              "pt-BR"
            );
            html += `
                            <div class="product-card p-3 mb-3 border rounded">
                                <div class="d-flex align-items-center">
                                    <div class="product-icon me-3">
                                        <i class="fas fa-box text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">${product.tipo_name}</h6>
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
                                                <div class="form-check">
                                                    <input class="form-check-input product-select" 
                                                           type="checkbox" 
                                                           value="${product.id}"
                                                           id="product-${product.id}">
                                                </div>
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
                            <p class="mb-0">Este pedido ainda não possui produtos cadastrados.</p>
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
    // Por enquanto, vamos apenas mostrar uma mensagem
    Swal.fire({
      title: "Impressão em Lote",
      text: `Imprimindo ${selectedProducts.length} QR codes...`,
      icon: "info",
    });
  });

  // Imprimir todos os QR codes do pedido (usando delegação de eventos)
  $(document).on("click", ".print-all", function () {
    const id = $(this).data("id");

    Swal.fire({
      title: "Impressão em Lote",
      text: "Deseja imprimir todos os QR codes deste pedido?",
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

  // Gerar PDF do pedido (usando delegação de eventos)
  $(document).on("click", ".generate-pdf", function () {
    const id = $(this).data("id");

    $.ajax({
      type: "POST",
      url: "src/controllers/ProductionOrderController.php",
      data: {
        action: "generate_pdf",
        id: id,
      },
      success: function (response) {
        const res = JSON.parse(response);
        if (res.success) {
          // Abrir PDF em nova aba ou fazer download
          window.open(res.path, "_blank");
        } else {
          Swal.fire({
            title: "Erro!",
            text: "Erro ao gerar PDF",
            icon: "error",
          });
        }
      },
    });
  });

  // Excluir pedido completo (usando delegação de eventos)
  $(document).on("click", ".delete-order", function () {
    const id = $(this).data("id");
    const orderNumber = $(this).data("order");
    const totalProducts = $(this).data("products");

    Swal.fire({
      title: "Atenção!",
      html: `
                Você está prestes a excluir o pedido <b>${orderNumber}</b> com <b>${totalProducts}</b> produtos.<br><br>
                Os produtos voltarão para o estoque.<br>
                Esta ação não pode ser desfeita!
            `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText:
        '<i class="fas fa-trash-alt me-2"></i>Sim, excluir pedido',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: "POST",
          url: "src/controllers/ProductionOrderController.php",
          data: {
            action: "delete_order",
            id: id,
          },
          success: function (response) {
            const res = JSON.parse(response);
            if (res.success) {
              Swal.fire({
                title: "Sucesso!",
                text: `Pedido ${orderNumber} excluído com sucesso!`,
                icon: "success",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: "Erro!",
                text: res.message || "Erro ao excluir pedido",
                icon: "error",
              });
            }
          },
          error: function () {
            Swal.fire({
              title: "Erro!",
              text: "Erro ao excluir pedido",
              icon: "error",
            });
          },
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
});
