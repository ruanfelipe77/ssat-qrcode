$(document).ready(function () {
  // Se não estamos na página de lotes, não registra handlers deste arquivo
  if (!document.getElementById("batches-table")) {
    return;
  }
  // Evitar backdrops presos ao fechar o modal de produtos
  (function setupModalCleanup() {
    const modalEl = document.getElementById("productsModal");
    if (modalEl) {
      modalEl.addEventListener("hidden.bs.modal", function () {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
      });
    }
  })();
  // Inicialização do DataTable
  if ($.fn.DataTable.isDataTable("#batches-table")) {
    $("#batches-table").DataTable().destroy();
  }

  // Helper: atualizar serial_start (readonly) e preview
  function setSerialStart(val) {
    $("#serial_start").val(val);
    updateSerialPreview();
  }

  // Buscar do servidor o próximo serial de início para o tipo selecionado
  function fetchNextSerialStart() {
    const tipoId = $("#tipo_id").val();
    if (!tipoId) { setSerialStart(""); return; }
    $.ajax({
      type: 'GET',
      url: 'src/controllers/BatchController.php',
      data: { action: 'get_next_serial_start', tipo_id: tipoId },
      success: function (response) {
        try {
          const res = typeof response === 'string' ? JSON.parse(response) : response;
          if (res && res.success) {
            setSerialStart(res.next_start || 1);
          } else {
            setSerialStart(1);
          }
        } catch (e) {
          setSerialStart(1);
        }
      },
      error: function () { setSerialStart(1); }
    });
  }

  // Ao mudar o tipo, buscar novo número inicial automático
  $(document).on('change', '#tipo_id', function(){
    fetchNextSerialStart();
  });

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
    const modalElement = document.getElementById("batchModal");
    if (modalElement) {
      const modal = new bootstrap.Modal(modalElement);
      // resetar campos do range
      $("#serial_start").val("").prop('readonly', true);
      $("#quantity").val("");
      $("#serial_preview").val("");
      // buscar número inicial quando escolher o tipo (ou já se tiver selecionado)
      fetchNextSerialStart();
      modal.show();
    } else {
      console.error("Modal batchModal não encontrado");
      Swal.fire({
        title: "Erro!",
        text: "Modal não encontrado. Recarregue a página.",
        icon: "error",
      });
    }
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
            try {
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
            } catch (e) {
              console.error("Erro ao processar resposta:", response);
              Swal.fire({
                title: "Erro!",
                text: "Erro ao processar resposta do servidor",
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
        try {
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
          const el = document.getElementById("productsModal");
          const modal = bootstrap.Modal.getOrCreateInstance(el);
          modal.show();
        } catch (e) {
          console.error("Erro ao processar produtos:", response);
          $(".products-list").html(`
            <div class="text-center text-danger py-5">
              <i class="fas fa-exclamation-triangle mb-3" style="font-size: 3rem;"></i>
              <h5>Erro ao carregar produtos</h5>
              <p class="mb-0">Não foi possível carregar os produtos deste lote.</p>
            </div>
          `);
        }
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
    // Abrir os QRs selecionados em novas abas (implementação básica)
    selectedProducts.forEach((id, idx) => {
      setTimeout(() => {
        window.open(
          `src/controllers/QrController.php?id=${encodeURIComponent(id)}&s=300`,
          "_blank"
        );
      }, idx * 150);
    });
    Swal.fire({
      title: "Impressão em Lote",
      text: `Abrindo ${selectedProducts.length} QR codes em novas abas...`,
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
        // Você pode implementar um endpoint para gerar PDF/ZIP; por ora, selecione manualmente e use "Imprimir Selecionados".
        Swal.fire({
          title: "Em breve",
          text: "Para imprimir em lote, selecione os itens e clique em 'Imprimir Selecionados'.",
          icon: "info",
        });
      }
    });
  });

  // Imprimir QR individual (abrir em nova aba)
  $(document).on("click", ".print-qrcode", function () {
    const id = $(this).data("id");
    if (!id) return;
    window.open(
      `src/controllers/QrController.php?id=${encodeURIComponent(id)}&s=300`,
      "_blank"
    );
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
            try {
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
            } catch (e) {
              console.error("Erro ao processar resposta:", response);
              Swal.fire({
                title: "❌ Erro!",
                text: "Erro ao processar resposta do servidor",
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
