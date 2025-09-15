$(document).ready(function () {
  // Garantir que backdrops não fiquem presos ao fechar o modal
  (function setupModalCleanup() {
    const modalEl = document.getElementById("productsModal");
    if (modalEl) {
      modalEl.addEventListener("hidden.bs.modal", function () {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
      });

  // ==============================
  // Edição de Pedido
  // ==============================
  $(document).on("click", ".edit-order", async function () {
    const orderId = $(this).data("id");
    const orderNumber = $(this).data("order");
    const modalEl = document.getElementById("orderModal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    try {
      // Carregar dados do pedido
      const [orderRes, orderProductsRes, availableRes] = await Promise.all([
        $.get("src/controllers/ProductionOrderController.php", { id: orderId }),
        $.get("src/controllers/ProductionOrderController.php", { id: orderId, products: true }),
        $.get("src/controllers/ProductionOrderController.php", { available_products: true }),
      ]);

      const order = typeof orderRes === "string" ? JSON.parse(orderRes) : orderRes;
      const orderProducts = typeof orderProductsRes === "string" ? JSON.parse(orderProductsRes) : orderProductsRes;
      const availableProducts = typeof availableRes === "string" ? JSON.parse(availableRes) : availableRes;

      // Preencher cabeçalho do formulário
      $("#order_action").val("update_order");
      $("#order_id").val(orderId);
      $("#client_id").val(order.client_id);
      $("#order_date").val(order.order_date);
      $("#warranty").val(order.warranty);
      $(".order-modal-title-text").text(`Editar Pedido ${orderNumber}`);

      // Construir UI dos produtos: unir disponíveis + já do pedido
      const selectedIds = new Set(orderProducts.map((p) => String(p.id)));
      // Mapear já-do-pedido por id para manter garantias/serial, etc.
      const existingById = {}; orderProducts.forEach(p => { existingById[String(p.id)] = p; });

      // Unir listas: disponíveis + os que já estão no pedido (podem não estar em available)
      const union = [...availableProducts];
      orderProducts.forEach(p => {
        if (!union.find(u => String(u.id) === String(p.id))) union.push(p);
      });

      // Agrupar por lote (batch_number), com fallbacks melhores
      // Regra:
      // 1) Se tiver batch_number, usar.
      // 2) Se não tiver batch_number mas tiver production_batch_id, rotular como `Lote <id>` para não cair em "Sem Lote".
      // 3) Só usar "Sem Lote" se realmente não houver vínculo de lote.
      const groups = {};
      const groupHasSelected = {};
      union.forEach((p) => {
        let batch = (p.batch_number && String(p.batch_number).trim() !== '')
          ? p.batch_number
          : (p.production_batch_id ? `Lote ${p.production_batch_id}` : 'Sem Lote');
        if (!groups[batch]) groups[batch] = [];
        groups[batch].push(p);
        if (selectedIds.has(String(p.id))) groupHasSelected[batch] = true;
      });

      // Montar HTML
      let html = '<div id="batches-accordion-edit">';
      Object.keys(groups)
        .sort((a, b) => {
          const sa = groupHasSelected[a] ? 1 : 0;
          const sb = groupHasSelected[b] ? 1 : 0;
          if (sa !== sb) return sb - sa; // grupos com selecionados primeiro
          // empurrar 'Sem Lote' para o final
          if (a === 'Sem Lote' && b !== 'Sem Lote') return 1;
          if (b === 'Sem Lote' && a !== 'Sem Lote') return -1;
          return String(a).localeCompare(String(b), 'pt-BR', {numeric:true, sensitivity:'base'});
        })
        .forEach((batch) => {
        const products = groups[batch].sort((a,b)=> String(a.serial_number).localeCompare(String(b.serial_number)));
        const collapseId = `batch-edit-${batch.replace(/[^a-zA-Z0-9]/g,'')}`;
        html += `
          <div class="batch-group mb-2" data-batch="${batch}">
            <div class="batch-header p-3 bg-white border-bottom d-flex justify-content-between align-items-center" 
                 data-bs-toggle="collapse" data-bs-target="#${collapseId}" style="cursor:pointer;">
              <div class="d-flex align-items-center">
                <i class="fas fa-chevron-down me-3 batch-chevron text-primary" style="transition: transform 0.2s;"></i>
                <div>
                  <h6 class="mb-1 text-primary"><i class="fas fa-layer-group me-2"></i>${batch}</h6>
                  <small class="text-muted"><span class="batch-count">${products.length}</span> produtos • <span class="batch-selected">0</span> selecionados</small>
                </div>
              </div>
            </div>
            <div class="collapse show" id="${collapseId}">
              <div class="batch-products p-2" style="background:#f8f9fa;">
                <div class="row g-2">
        `;
        products.forEach((p) => {
          const checked = selectedIds.has(String(p.id)) ? "checked" : "";
          html += `
            <div class="col-md-6">
              <div class="product-card p-2 bg-white border rounded shadow-sm h-100" data-type="${(p.tipo_name||'').toLowerCase()}" data-serial="${p.serial_number||''}" data-batch="${batch}">
                <div class="form-check h-100">
                  <input class="form-check-input product-select" type="checkbox" value="${p.id}" id="product_edit_${p.id}" name="products[]" ${checked}>
                  <label class="form-check-label w-100 h-100 d-flex flex-column" for="product_edit_${p.id}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <div class="d-flex align-items-center">
                        <i class="fas fa-microchip text-primary me-2"></i>
                        <strong class="product-type" style="font-size:0.9rem;">${p.tipo_name || ''}</strong>
                      </div>
                      <i class="fas ${checked ? 'fa-check-circle' : 'fa-plus-circle'} text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex flex-wrap gap-1 mb-1">
                        <span class="badge bg-dark" style="font-size:0.7rem;"><i class="fas fa-hashtag me-1"></i>${p.serial_number||''}</span>
                        <span class="badge bg-warning text-dark" style="font-size:0.7rem;"><i class="fas fa-shield-alt me-1"></i>${p.warranty||''}</span>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          `;
        });
        html += `
                </div>
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';

      $("#products-container").html(html);

      // Atualizar contadores e estilos já existentes
      if (typeof updateAllCounts === 'function') updateAllCounts();
      if (typeof updateProductCardStyles === 'function') updateProductCardStyles();

      modal.show();
    } catch (e) {
      console.error(e);
      Swal.fire({ title: "Erro", text: "Não foi possível carregar o pedido para edição.", icon: "error" });
    }
  });
    }
  })();
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
        const el = document.getElementById("productsModal");
        // Reutiliza ou cria instância única do modal para evitar múltiplos backdrops
        const modal = bootstrap.Modal.getOrCreateInstance(el);
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
    // Abrir página de etiquetas com os IDs selecionados
    const idsParam = selectedProducts.join(",");
    window.open(
      `src/controllers/LabelController.php?ids=${encodeURIComponent(idsParam)}`,
      "_blank"
    );
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
        // Abrir página de etiquetas para o pedido completo
        window.open(
          `src/controllers/LabelController.php?order_id=${encodeURIComponent(
            id
          )}`,
          "_blank"
        );
      }
    });
  });

  // Imprimir etiqueta individual (abrir página de etiqueta)
  $(document).on("click", ".print-qrcode", function () {
    const id = $(this).data("id");
    if (!id) return;
    window.open(
      `src/controllers/LabelController.php?id=${encodeURIComponent(id)}`,
      "_blank"
    );
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
