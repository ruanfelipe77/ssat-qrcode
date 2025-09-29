// Variáveis globais - usar window para evitar conflitos
window.templatesTable = window.templatesTable || null;
window.assembliesTable = window.assembliesTable || null;
window.compositeProductsTable = window.compositeProductsTable || null;
window.currentAssemblyId = window.currentAssemblyId || null;
window.templateItemCounter = window.templateItemCounter || 0;
// Contador incremental para IDs temporários de componentes adicionados no modal
window.tempComponentCounter = window.tempComponentCounter || 0;

// Função auxiliar para tratar respostas JSON de forma segura
function parseResponse(response) {
  if (typeof response === "object" && response !== null) {
    return response; // Já é um objeto
  }
  if (typeof response === "string") {
    try {
      return JSON.parse(response);
    } catch (e) {
      console.error("Erro ao parsear JSON:", e, response);
      throw new Error("Resposta inválida do servidor");
    }
  }
  throw new Error("Tipo de resposta não suportado");
}

$(document).ready(function () {
  // Só inicializar se estivermos na página com tabs (composites.php)
  if ($("#compositesTabs").length > 0) {
    initializeTables();
    loadInitialData();

    // Event listeners para tabs
    $('#compositesTabs button[data-bs-toggle="tab"]').on(
      "shown.bs.tab",
      function (e) {
        const target = $(e.target).attr("data-bs-target");
        if (target === "#templates") {
          window.templatesTable.columns.adjust().draw();
          if (window.templatesTable) window.templatesTable.ajax.reload();
        } else if (target === "#assemblies") {
          window.assembliesTable.columns.adjust().draw();
          if (window.assembliesTable) window.assembliesTable.ajax.reload();
        } else if (target === "#products") {
          window.compositeProductsTable.columns.adjust().draw();
          if (window.compositeProductsTable)
            window.compositeProductsTable.ajax.reload();
        }
      }
    );
  }
});

function initializeTables() {
  // Tabela de Templates
  window.templatesTable = $("#templatesTable").DataTable({
    ajax: {
      url: "src/controllers/CompositeController.php?action=get_templates",
      dataSrc: function (json) {
        if (typeof json === "string") {
          try {
            json = JSON.parse(json);
          } catch (e) {
            console.error("Erro ao parsear JSON:", e);
            return [];
          }
        }
        return Array.isArray(json) ? json : [];
      },
      error: function (xhr, error, thrown) {
        console.error("Erro na requisição de templates:", error, thrown);
        Swal.fire("Erro!", "Erro ao carregar templates.", "error");
      },
    },
    columns: [
      { data: "id" },
      { data: "tipo_name" },
      { data: "version" },
      {
        data: "is_active",
        render: function (data) {
          return data == 1
            ? '<span class="badge bg-success">Ativo</span>'
            : '<span class="badge bg-secondary">Inativo</span>';
        },
      },
      { data: "items_count" },
      {
        data: "created_at",
        render: function (data) {
          return new Date(data).toLocaleDateString("pt-BR");
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          let buttons = `
                        <button class="btn btn-sm btn-outline-warning" onclick="editTemplate(${row.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>`;

          if (row.is_active == 0) {
            buttons += `
                            <button class="btn btn-sm btn-outline-success" onclick="activateTemplate(${row.id})" title="Ativar">
                                <i class="fas fa-check"></i>
                            </button>`;
          }

          buttons += `
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(${row.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>`;

          return buttons;
        },
      },
    ],
    pagingType: "full_numbers",
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    responsive: true,
    dom: "frtip",
    order: [[5, "desc"]],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
    },
  });

  // Tabela de Assemblies
  window.assembliesTable = $("#assembliesTable").DataTable({
    ajax: {
      url: "src/controllers/CompositeController.php?action=get_assemblies",
      dataSrc: function (json) {
        if (typeof json === "string") {
          try {
            json = JSON.parse(json);
          } catch (e) {
            console.error("Erro ao parsear JSON:", e);
            return [];
          }
        }
        return Array.isArray(json) ? json : [];
      },
      error: function (xhr, error, thrown) {
        console.error("Erro na requisição de assemblies:", error, thrown);
        Swal.fire("Erro!", "Erro ao carregar montagens.", "error");
      },
    },
    columns: [
      { data: "id" },
      { data: "composite_tipo_name" },
      {
        data: "status",
        render: function (data) {
          const statusMap = {
            draft: '<span class="badge bg-secondary">Rascunho</span>',
            in_progress: '<span class="badge bg-warning">Em Progresso</span>',
            finalized: '<span class="badge bg-success">Finalizada</span>',
            disassembled: '<span class="badge bg-danger">Desmontada</span>',
          };
          return statusMap[data] || data;
        },
      },
      { data: "components_count" },
      { data: "composite_serial" },
      { data: "created_by_name" },
      {
        data: "created_at",
        render: function (data) {
          return new Date(data).toLocaleDateString("pt-BR");
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          let buttons = `
                        <button class="btn btn-sm btn-outline-info" onclick="viewAssembly(${row.id})" title="Visualizar Detalhes">
                            <i class="fas fa-search-plus"></i>
                        </button>`;

          if (row.status === "in_progress") {
            buttons += `
                            <button class="btn btn-sm btn-outline-warning" onclick="editAssembly(${row.id})" title="Continuar Montagem">
                                <i class="fas fa-tools"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAssembly(${row.id})" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>`;
          }

          if (row.status === "finalized") {
            buttons += `
                            <button class="btn btn-sm btn-outline-danger" onclick="disassembleAssembly(${row.id})" title="Desmontar">
                                <i class="fas fa-wrench"></i>
                            </button>`;
          }

          return buttons;
        },
      },
    ],
    pagingType: "full_numbers",
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    responsive: true,
    dom: "frtip",
    order: [[6, "desc"]],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
    },
  });

  // Tabela de Produtos Compostos
  window.compositeProductsTable = $("#compositeProductsTable").DataTable({
    ajax: {
      url: "src/controllers/CompositeController.php?action=get_composite_products",
      dataSrc: function (json) {
        if (typeof json === "string") {
          try {
            json = JSON.parse(json);
          } catch (e) {
            console.error("Erro ao parsear JSON:", e);
            return [];
          }
        }
        return Array.isArray(json) ? json : [];
      },
      error: function (xhr, error, thrown) {
        console.error(
          "Erro na requisição de produtos compostos:",
          error,
          thrown
        );
        Swal.fire("Erro!", "Erro ao carregar produtos compostos.", "error");
      },
    },
    columns: [
      { data: "id" },
      { data: "serial_number" },
      { data: "tipo_name" },
      { data: "components_count" },
      {
        data: "created_at",
        render: function (data) {
          return new Date(data).toLocaleDateString("pt-BR");
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          let buttons = `
                        <button class="btn btn-sm btn-outline-primary" onclick="viewCompositeProduct(${row.id})" title="Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="generateQR(${row.id})" title="QR Code">
                            <i class="fas fa-qrcode"></i>
                        </button>`;

          if (row.assembly_status === "finalized") {
            buttons += `
                            <button class="btn btn-sm btn-outline-danger" onclick="disassembleAssembly(${row.assembly_id})" title="Desmontar">
                                <i class="fas fa-wrench"></i>
                            </button>`;
          }

          return buttons;
        },
      },
    ],
    pagingType: "full_numbers",
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    responsive: true,
    dom: "frtip",
    order: [[4, "desc"]],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
    },
  });
}

function loadInitialData() {
  // Carregar tipos compostos para o modal de template
  $.get(
    "src/controllers/CompositeController.php?action=get_composite_tipos",
    function (response) {
      try {
        const data =
          typeof response === "string" ? JSON.parse(response) : response;
        const select = $("#template_tipo_id");
        select.empty().append('<option value="">Selecione...</option>');
        if (Array.isArray(data)) {
          data.forEach((tipo) => {
            select.append(`<option value="${tipo.id}">${tipo.nome}</option>`);
          });
        }
      } catch (e) {
        console.error("Erro ao carregar tipos compostos:", e);
      }
    }
  ).fail(function () {
    console.error("Erro na requisição de tipos compostos");
  });

  // Carregar templates ativos para o modal de assembly
  $.get(
    "src/controllers/CompositeController.php?action=get_templates",
    function (response) {
      try {
        const data =
          typeof response === "string" ? JSON.parse(response) : response;
        const select = $("#assembly_template_id");
        select
          .empty()
          .append('<option value="">Selecione um template...</option>');
        if (Array.isArray(data)) {
          data
            .filter((t) => t.is_active == 1)
            .forEach((template) => {
              select.append(
                `<option value="${template.id}">${template.tipo_name} v${template.version}</option>`
              );
            });
        }
      } catch (e) {
        console.error("Erro ao carregar templates:", e);
      }
    }
  ).fail(function () {
    console.error("Erro na requisição de templates");
  });
}

// Funções de Template
function addTemplateItem(preset = null) {
  window.templateItemCounter++;
  const currentId = window.templateItemCounter;
  const html = `
        <div class="row mb-2 template-item" id="item_${currentId}">
            <div class="col-md-5">
                <select class="form-select component-tipo-select" name="items[${currentId}][component_tipo_id]" required>
                    <option value="">Selecione o componente...</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="items[${currentId}][quantity]" placeholder="Qtd" value="1" min="1" required>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="items[${currentId}][is_optional]" value="1">
                    <label class="form-check-label">Opcional</label>
                </div>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="items[${currentId}][notes]" placeholder="Obs">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTemplateItem(${currentId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;

  $("#templateItems").append(html);

  // Carregar tipos individuais para o novo select
  $.get(
    "src/controllers/CompositeController.php?action=get_individual_tipos",
    function (response) {
      try {
        const data =
          typeof response === "string" ? JSON.parse(response) : response;
        const select = $(`#item_${currentId} .component-tipo-select`);
        if (Array.isArray(data)) {
          data.forEach((tipo) => {
            select.append(`<option value="${tipo.id}">${tipo.nome}</option>`);
          });
        }

        // Aplicar preset, se fornecido
        if (preset) {
          select.val(preset.component_tipo_id);
          $(`#item_${currentId} input[name*="quantity"]`).val(
            preset.quantity ?? 1
          );
          $(`#item_${currentId} input[name*="is_optional"]`).prop(
            "checked",
            String(preset.is_optional) === "1"
          );
          $(`#item_${currentId} input[name*="notes"]`).val(preset.notes ?? "");
        }
      } catch (e) {
        console.error("Erro ao carregar tipos individuais:", e);
      }
    }
  ).fail(function () {
    console.error("Erro na requisição de tipos individuais");
  });
}

function removeTemplateItem(id) {
  $(`#item_${id}`).remove();
}

function saveTemplate() {
  const formData = new FormData($("#templateForm")[0]);
  formData.append(
    "action",
    $("#template_id").val() ? "update_template" : "create_template"
  );

  // Processar itens do template
  const items = [];
  $(".template-item").each(function () {
    const item = {
      component_tipo_id: $(this)
        .find('select[name*="component_tipo_id"]')
        .val(),
      quantity: $(this).find('input[name*="quantity"]').val(),
      is_optional: $(this).find('input[name*="is_optional"]').is(":checked")
        ? 1
        : 0,
      notes: $(this).find('input[name*="notes"]').val(),
    };
    if (item.component_tipo_id) {
      items.push(item);
    }
  });

  formData.delete("items");
  formData.append("items", JSON.stringify(items));

  $.ajax({
    url: "src/controllers/CompositeController.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (result) {
      try {
        // Caso algum servidor não respeite dataType json
        if (typeof result === "string") {
          result = JSON.parse(result);
        }
      } catch (e) {
        console.error("Falha ao parsear resposta:", e);
        Swal.fire("Erro!", "Resposta inválida do servidor.", "error");
        return;
      }

      if (result && result.success) {
        Swal.fire("Sucesso!", "Template salvo com sucesso!", "success");
        $("#templateModal").modal("hide");
        if (window.templatesTable) window.templatesTable.ajax.reload();
        loadInitialData(); // Recarregar templates para assembly
      } else {
        const msg =
          result && result.message
            ? result.message
            : "Erro ao salvar template.";
        Swal.fire("Erro!", msg, "error");
      }
    },
    error: function (xhr) {
      let msg = "Erro de comunicação com o servidor.";
      if (xhr && xhr.responseText) {
        try {
          const err = JSON.parse(xhr.responseText);
          if (err && err.error) msg = err.error;
        } catch (_) {}
      }
      Swal.fire("Erro!", msg, "error");
    },
  });
}

function editTemplate(id) {
  $.get(
    `src/controllers/CompositeController.php?action=get_template&id=${id}`,
    function (data) {
      const template = data.template;
      const items = data.items;

      $("#template_id").val(template.id);
      $("#template_tipo_id").val(template.tipo_id);
      $("#template_version").val(template.version);
      $("#template_notes").val(template.notes);

      // Limpar itens existentes
      $("#templateItems").empty();
      window.templateItemCounter = 0;

      // Adicionar itens do template
      items.forEach((item) => {
        addTemplateItem({
          component_tipo_id: item.component_tipo_id,
          quantity: item.quantity,
          is_optional: item.is_optional,
          notes: item.notes,
        });
      });

      $(".modal-title").text("Editar Template");
      $("#templateModal").modal("show");
    }
  );
}

function activateTemplate(id) {
  Swal.fire({
    title: "Ativar Template?",
    text: "Isso desativará outros templates do mesmo produto.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sim, ativar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "activate_template",
          id: id,
        },
        function (response) {
          const result = JSON.parse(response);
          if (result.success) {
            Swal.fire("Sucesso!", "Template ativado!", "success");
            if (window.templatesTable) window.templatesTable.ajax.reload();
            loadInitialData();
          } else {
            Swal.fire("Erro!", "Erro ao ativar template.", "error");
          }
        }
      ).fail(function (xhr, status, error) {
        console.error(
          "Erro na desmontagem:",
          status,
          error,
          xhr && xhr.responseText
        );
        Swal.fire({
          title: "Erro!",
          text: "Falha na comunicação com o servidor ao desmontar.",
          icon: "error",
        });
      });
    }
  });
}

function deleteTemplate(id) {
  Swal.fire({
    title: "Excluir Template?",
    text: "Esta ação não pode ser desfeita.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "delete_template",
          id: id,
        },
        function (response) {
          const result = JSON.parse(response);
          if (result.success) {
            Swal.fire("Sucesso!", "Template excluído!", "success");
            if (window.templatesTable) window.templatesTable.ajax.reload();
          } else {
            Swal.fire(
              "Erro!",
              result.message || "Erro ao excluir template.",
              "error"
            );
          }
        }
      );
    }
  });
}

// Funções de Assembly
function showTemplateRequirements() {
  const templateId = $("#assembly_template_id").val();
  if (!templateId) {
    $("#templateRequirements").hide();
    $("#saveDraftBtn, #finalizeBtn").prop("disabled", true);
    return;
  }

  $.get(
    `src/controllers/CompositeController.php?action=get_template&id=${templateId}`,
    function (data) {
      const items = data.items;
      let html = '<div class="list-group">';

      items.forEach((item) => {
        const badge =
          item.is_optional == 1
            ? '<span class="badge bg-secondary ms-2">Opcional</span>'
            : '<span class="badge bg-primary ms-2">Obrigatório</span>';

        html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${item.component_tipo_name} (${item.quantity}x)</span>
                    ${badge}
                </div>
            `;
      });

      html += "</div>";
      $("#requirementsList").html(html);
      $("#templateRequirements").show();

      // Carregar produtos disponíveis
      loadAvailableProducts();

      // Habilitar botões
      $("#saveDraftBtn, #finalizeBtn").prop("disabled", false);
    }
  );
}

function createAssemblyIfNeeded() {
  if (window.currentAssemblyId) {
    return Promise.resolve(window.currentAssemblyId);
  }

  const templateId = $("#assembly_template_id").val();
  if (!templateId) {
    console.error("Nenhum template selecionado");
    return Promise.reject("Nenhum template selecionado");
  }

  return new Promise((resolve, reject) => {
    $.post(
      "src/controllers/CompositeController.php",
      {
        action: "create_assembly",
        template_id: templateId,
      },
      function (response) {
        try {
          const result = parseResponse(response);
          if (result.success) {
            window.currentAssemblyId = result.assembly_id;
            resolve(result.assembly_id);
          } else {
            reject(result.message || "Erro ao criar assembly");
          }
        } catch (e) {
          console.error("Erro ao processar resposta:", e);
          reject(e.message);
        }
      }
    ).fail(function (xhr, status, error) {
      console.error(
        "Erro na requisição AJAX:",
        status,
        error,
        xhr.responseText
      );
      reject("Erro de comunicação com o servidor");
    });
  });
}

function saveDraftAssembly() {
  // Verificar se já está processando
  if ($("#saveDraftBtn").prop("disabled")) {
    return;
  }

  // Desabilitar botão para evitar cliques duplos
  $("#saveDraftBtn")
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin me-1"></i>Salvando...');

  createAssemblyIfNeeded()
    .then((assemblyId) => {
      // Verificar se há serial para salvar
      const serial = $("#composite_serial").val().trim();

      if (serial) {
        // Salvar o serial na assembly
        return $.post("src/controllers/CompositeController.php", {
          action: "update_assembly_serial",
          assembly_id: assemblyId,
          composite_serial: serial,
        }).then(function (response) {
          const result = parseResponse(response);
          if (!result.success) {
            throw new Error(result.message || "Erro ao salvar serial");
          }
          return assemblyId;
        });
      } else {
        return assemblyId;
      }
    })
    .then((assemblyId) => {
      // Primeiro, remover componentes marcados para remoção
      if (window.componentsToRemove && window.componentsToRemove.length > 0) {
        return removeComponentsFromAssembly(assemblyId);
      } else {
        return assemblyId;
      }
    })
    .then((assemblyId) => {
      // Depois, salvar componentes temporários se houver
      const newComponents = tempComponents.filter((comp) => !comp.existing);
      if (newComponents.length > 0) {
        return saveTempComponents(assemblyId);
      } else {
        return assemblyId;
      }
    })
    .then((assemblyId) => {
      // Carregar componentes para garantir que estão salvos
      loadAssemblyComponents();

      // Marcar como fechado por sucesso
      $("#assemblyModal").data("success", true);

      // Remover foco e fechar modal com delay para evitar aria-hidden
      setTimeout(() => {
        $("#saveDraftBtn").blur();
        $("#assemblyModal").modal("hide");
      }, 100);

      // Recarregar tabela
      if (window.assembliesTable && window.assembliesTable.ajax) {
        window.assembliesTable.ajax.reload();
      } else if (
        typeof assembliesTable !== "undefined" &&
        assembliesTable.ajax
      ) {
        assembliesTable.ajax.reload();
      } else {
        // Como fallback, recarregar a página após um delay
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      }

      // Mostrar sucesso após fechar modal
      setTimeout(() => {
        const isEditing =
          window.currentAssemblyId && window.currentAssemblyId == assemblyId;
        const message = isEditing
          ? `Montagem #${assemblyId} atualizada com sucesso!`
          : `Nova montagem criada com sucesso! ID: ${assemblyId}`;

        Swal.fire({
          title: "Sucesso!",
          text: message,
          icon: "success",
          timer: 3000,
          showConfirmButton: false,
        });
      }, 800);
    })
    .catch((error) => {
      console.error("Erro ao salvar rascunho:", error);

      // Reabilitar botão
      $("#saveDraftBtn")
        .prop("disabled", false)
        .html('<i class="fas fa-save me-1"></i>Salvar Progresso');

      const errorMessage =
        typeof error === "string" ? error : "Erro desconhecido";
      Swal.fire({
        title: "Erro!",
        text: errorMessage,
        icon: "error",
        icon: "error",
      });
    });
}

function cancelAssembly() {
  if (window.currentAssemblyId) {
    // Se já foi criado um rascunho, excluir
    $.post(
      "src/controllers/CompositeController.php",
      {
        action: "delete_assembly",
        id: window.currentAssemblyId,
      },
      function (response) {
        // Limpar independente do resultado
        window.currentAssemblyId = null;
      }
    );
  }

  // Limpar componentes temporários e remoções
  tempComponents = [];
  window.componentsToRemove = [];

  // Limpar formulário
  $("#assembly_template_id").val("");
  $("#composite_serial").val("");
  $("#templateRequirements").hide();
  $("#componentsList").empty();

  // Resetar botões
  $("#saveDraftBtn")
    .prop("disabled", true)
    .html('<i class="fas fa-save me-1"></i>Salvar Progresso');
  $("#finalizeBtn")
    .prop("disabled", true)
    .html('<i class="fas fa-check me-1"></i>Finalizar Montagem');

  window.currentAssemblyId = null;
}

function loadAvailableProducts(tipoId = null) {
  const url = tipoId
    ? `src/controllers/CompositeController.php?action=get_available_products&tipo_id=${tipoId}`
    : "src/controllers/CompositeController.php?action=get_available_products";

  $.get(url, function (response) {
    try {
      const data =
        typeof response === "string" ? JSON.parse(response) : response;
      const select = $("#available_products");
      select
        .empty()
        .append('<option value="">Selecione um produto...</option>');
      if (Array.isArray(data)) {
        data.forEach((product) => {
          select.append(
            `<option value="${product.id}">${product.serial_number} - ${product.tipo_name}</option>`
          );
        });
      }
    } catch (e) {
      console.error("Erro ao carregar produtos disponíveis:", e);
    }
  }).fail(function () {
    console.error("Erro na requisição de produtos disponíveis");
  });
}

// Array para armazenar componentes temporários no modal
let tempComponents = [];

function addComponentToAssembly() {
  const productId = $("#available_products").val();
  const productText = $("#available_products option:selected").text();

  if (!productId) {
    Swal.fire({
      title: "Atenção!",
      text: "Selecione um produto para adicionar.",
      icon: "warning",
    });
    return;
  }

  // Verificar se o produto já foi adicionado
  if (tempComponents.find((comp) => comp.product_id == productId)) {
    Swal.fire({
      title: "Atenção!",
      text: "Este produto já foi adicionado.",
      icon: "warning",
    });
    return;
  }

  // Adicionar ao array temporário
  tempComponents.push({
    product_id: productId,
    product_text: productText,
    temp_id: ++window.tempComponentCounter, // ID temporário incremental e único
  });

  // Atualizar lista visual
  updateComponentsList();

  // Limpar select
  $("#available_products").val("");
}

function updateComponentsList() {
  let html = '<div class="list-group">';

  if (tempComponents.length === 0) {
    html +=
      '<div class="list-group-item text-muted">Nenhum componente adicionado</div>';
  } else {
    tempComponents.forEach((component) => {
      const statusText = component.existing
        ? '<small class="text-success">Salvo</small>'
        : '<small class="text-warning">Temporário - não salvo</small>';

      html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <strong>${component.product_text}</strong><br>
                        ${statusText}
                    </span>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeTempComponent(${component.temp_id})" title="Remover componente">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
    });
  }

  html += "</div>";
  $("#componentsList").html(html);

  const existingCount = tempComponents.filter((c) => c.existing).length;
  const newCount = tempComponents.filter((c) => !c.existing).length;
}

function removeTempComponent(tempId) {
  // Encontrar o componente que será removido
  const componentToRemove = tempComponents.find(
    (comp) => comp.temp_id == tempId
  );

  if (
    componentToRemove &&
    componentToRemove.existing &&
    window.currentAssemblyId
  ) {
    // Se é um componente existente e há assembly ativa, marcar para remoção
    // Adicionar à lista de componentes para remover
    if (!window.componentsToRemove) {
      window.componentsToRemove = [];
    }
    window.componentsToRemove.push(componentToRemove.product_id);
  }

  // Remover do array temporário
  tempComponents = tempComponents.filter((comp) => comp.temp_id !== tempId);
  updateComponentsList();
}

function saveTempComponents(assemblyId) {
  // Filtrar apenas componentes novos (não existentes)
  const newComponents = tempComponents.filter((comp) => !comp.existing);

  if (newComponents.length === 0) {
    return Promise.resolve(assemblyId);
  }

  // Criar promises para salvar cada componente novo
  const savePromises = newComponents.map((component) => {
    return new Promise((resolve, reject) => {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "add_component",
          assembly_id: assemblyId,
          component_product_id: component.product_id,
        },
        function (response) {
          try {
            const result = parseResponse(response);
            if (result.success) {
              resolve(result);
            } else {
              reject(new Error(result.message || "Erro ao salvar componente"));
            }
          } catch (e) {
            reject(e);
          }
        }
      ).fail(function (xhr, status, error) {
        reject(new Error("Erro de comunicação: " + error));
      });
    });
  });

  // Aguardar todos os componentes serem salvos
  return Promise.all(savePromises)
    .then(() => {
      // Limpar array temporário
      tempComponents = [];
      return assemblyId;
    })
    .catch((error) => {
      console.error("Erro ao salvar componentes:", error);
      throw error;
    });
}

function removeComponentsFromAssembly(assemblyId) {
  // Criar promises para remover cada componente
  const removePromises = window.componentsToRemove.map((productId) => {
    return new Promise((resolve, reject) => {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "remove_component",
          assembly_id: assemblyId,
          component_product_id: productId,
        },
        function (response) {
          try {
            const result = parseResponse(response);
            if (result.success) {
              resolve(result);
            } else {
              reject(new Error(result.message || "Erro ao remover componente"));
            }
          } catch (e) {
            reject(e);
          }
        }
      ).fail(function (xhr, status, error) {
        reject(new Error("Erro de comunicação: " + error));
      });
    });
  });

  // Aguardar todos os componentes serem removidos
  return Promise.all(removePromises)
    .then(() => {
      // Limpar lista de remoções
      window.componentsToRemove = [];
      return assemblyId;
    })
    .catch((error) => {
      console.error("Erro ao remover componentes:", error);
      throw error;
    });
}

function loadExistingComponentsToTemp(components) {
  tempComponents = [];

  if (components && components.length > 0) {
    components.forEach((component) => {
      tempComponents.push({
        product_id: component.component_product_id,
        product_text: `${component.component_serial} - ${component.component_tipo_name}`,
        temp_id: Date.now() + Math.random(), // ID temporário único
        existing: true, // Marcar como existente para não duplicar ao salvar
      });
    });
  }

  updateComponentsList();
}

function loadAssemblyComponents() {
  if (!window.currentAssemblyId) {
    return;
  }

  $.get(
    `src/controllers/CompositeController.php?action=get_assembly&id=${window.currentAssemblyId}`,
    function (data) {
      const components = data.components || [];
      let html = '<div class="list-group">';

      if (components.length === 0) {
        html +=
          '<div class="list-group-item text-muted">Nenhum componente adicionado</div>';
      } else {
        components.forEach((component) => {
          html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <strong>${component.component_serial}</strong><br>
                            <small class="text-muted">${component.component_tipo_name}</small>
                        </span>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeComponentFromAssembly(${component.component_product_id})" title="Remover componente">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
        });
      }

      html += "</div>";
      $("#componentsList").html(html);
    }
  ).fail(function (xhr, status, error) {
    console.error(
      "Erro ao carregar componentes:",
      status,
      error,
      xhr.responseText
    );
    $("#componentsList").html(
      '<div class="alert alert-danger">Erro ao carregar componentes</div>'
    );
  });
}

function removeComponentFromAssembly(productId) {
  $.post(
    "src/controllers/CompositeController.php",
    {
      action: "remove_component",
      assembly_id: window.currentAssemblyId,
      component_product_id: productId,
    },
    function (response) {
      try {
        const result = parseResponse(response);
        if (result.success) {
          loadAssemblyComponents();
          loadAvailableProducts();
        } else {
          Swal.fire("Erro!", result.message, "error");
        }
      } catch (e) {
        Swal.fire("Erro!", e.message, "error");
      }
    }
  );
}

function finalizeAssembly() {
  const serial = $("#composite_serial").val().trim();
  if (!serial) {
    Swal.fire({
      title: "Atenção!",
      text: "Informe o número serial do produto para finalizar a montagem.",
      icon: "warning",
    });
    return;
  }

  createAssemblyIfNeeded()
    .then((assemblyId) => {
      // Verificar se há serial para salvar
      if (serial) {
        return $.post("src/controllers/CompositeController.php", {
          action: "update_assembly_serial",
          assembly_id: assemblyId,
          composite_serial: serial,
        }).then(function (response) {
          const result = parseResponse(response);
          if (!result.success) {
            throw new Error(result.message || "Erro ao salvar serial");
          }
          return assemblyId;
        });
      } else {
        return assemblyId;
      }
    })
    .then((assemblyId) => {
      // Primeiro, remover componentes marcados para remoção
      if (window.componentsToRemove && window.componentsToRemove.length > 0) {
        return removeComponentsFromAssembly(assemblyId);
      } else {
        return assemblyId;
      }
    })
    .then((assemblyId) => {
      // Depois, salvar componentes temporários se houver
      const newComponents = tempComponents.filter((comp) => !comp.existing);
      if (newComponents.length > 0) {
        return saveTempComponents(assemblyId);
      } else {
        return assemblyId;
      }
    })
    .then((assemblyId) => {
      return $.post("src/controllers/CompositeController.php", {
        action: "finalize_assembly",
        assembly_id: assemblyId,
        composite_serial: serial,
      });
    })
    .then(function (response) {
      try {
        const result = parseResponse(response);
        if (result.success) {
          // Remover foco e marcar como sucesso
          $("#finalizeBtn").blur();
          $("#assemblyModal").data("success", true);

          // Fechar modal primeiro para evitar conflitos
          $("#assemblyModal").modal("hide");

          // Mostrar sucesso após fechar modal
          setTimeout(() => {
            Swal.fire({
              title: "Sucesso!",
              text: `Produto ${serial} montado e finalizado com sucesso!`,
              icon: "success",
              timer: 4000,
              showConfirmButton: false,
            });
          }, 300);
          if (window.assembliesTable) window.assembliesTable.ajax.reload();
          if (window.compositeProductsTable)
            window.compositeProductsTable.ajax.reload();
        } else {
          Swal.fire({
            title: "Erro!",
            text: result.message || "Erro ao finalizar montagem",
            icon: "error",
          });
        }
      } catch (e) {
        console.error("Erro ao processar resposta:", e);
        Swal.fire({
          title: "Erro!",
          text: "Erro ao processar resposta do servidor",
          icon: "error",
        });
      }
    })
    .fail(function (xhr, status, error) {
      console.error(
        "Erro na requisição de finalização:",
        status,
        error,
        xhr.responseText
      );
      Swal.fire({
        title: "Erro!",
        text: "Erro de comunicação com o servidor",
        icon: "error",
      });
    })
    .catch((error) => {
      console.error("Erro ao finalizar montagem:", error);
      const errorMessage =
        typeof error === "string"
          ? error
          : error.message || "Erro desconhecido";
      Swal.fire({
        title: "Erro!",
        text: errorMessage,
        icon: "error",
      });
    });
}

function deleteAssembly(id) {
  Swal.fire({
    title: "Excluir Montagem?",
    text: "Esta ação não pode ser desfeita.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "delete_assembly",
          id: id,
        },
        function (response) {
          const result = parseResponse(response);
          if (result.success) {
            Swal.fire({
              title: "Sucesso!",
              text: "Montagem excluída com sucesso.",
              icon: "success",
              timer: 1500,
              showConfirmButton: false,
            }).then(() => {
              // Recarregar ambas as tabelas
              if (window.assembliesTable) {
                window.assembliesTable.ajax.reload(null, false);
              }
              if (window.productsTable) {
                window.productsTable.ajax.reload(null, false);
              }
            });
          } else {
            Swal.fire(
              "Erro!",
              result.message || "Erro ao excluir montagem",
              "error"
            );
          }
        }
      ).fail(function (xhr, status, error) {
        console.error(
          "Erro ao excluir montagem:",
          status,
          error,
          xhr.responseText
        );
        Swal.fire("Erro!", "Erro de comunicação com o servidor", "error");
      });
    }
  });
}

function disassembleAssembly(assemblyId) {
  Swal.fire({
    title: "Desmontar e Excluir Produto?",
    text: "Os componentes voltarão ao estoque e o produto composto será excluído permanentemente. Esta ação não pode ser desfeita.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, desmontar e excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "disassemble",
          assembly_id: assemblyId,
        },
        function (response) {
          const result = parseResponse(response);
          if (result.success) {
            Swal.fire({
              title: "Sucesso!",
              text:
                result.message || "Produto desmontado e excluído com sucesso!",
              icon: "success",
            });
            if (window.assembliesTable) {
              window.assembliesTable.ajax.reload();
            } else if (
              typeof assembliesTable !== "undefined" &&
              assembliesTable.ajax
            ) {
              assembliesTable.ajax.reload();
            }

            if (window.compositeProductsTable) {
              window.compositeProductsTable.ajax.reload();
            } else if (
              typeof compositeProductsTable !== "undefined" &&
              compositeProductsTable &&
              compositeProductsTable.ajax
            ) {
              compositeProductsTable.ajax.reload();
            } else {
              // Como fallback final, recarregar a página
              setTimeout(() => window.location.reload(), 500);
            }
          } else {
            Swal.fire({
              title: "Erro!",
              text: result.message || "Erro desconhecido",
              icon: "error",
            });
          }
        }
      );
    }
  });
}

function generateQR(productId) {
  window.open(`src/controllers/QrController.php?id=${productId}`, "_blank");
}

// Funções para Assemblies/Montagens
function viewAssembly(id) {
  $.get(
    `src/controllers/CompositeController.php?action=get_assembly&id=${id}`,
    function (data) {
      const assembly = data.assembly;
      const components = data.components;

      // Status com cores
      const statusBadge = {
        in_progress:
          '<span class="badge bg-warning"><i class="fas fa-cog fa-spin me-1"></i>Em Progresso</span>',
        completed:
          '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Finalizada</span>',
      };

      let html = `
            <div class="card border-0">
                <div class="card-header bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-1"><i class="fas fa-info-circle text-primary me-2"></i>Informações Gerais</h6>
                            <p class="mb-1"><strong>ID:</strong> #${
                              assembly.id
                            }</p>
                            <p class="mb-1"><strong>Produto:</strong> ${
                              assembly.composite_tipo_name || "N/A"
                            }</p>
                            <p class="mb-0"><strong>Status:</strong> ${
                              statusBadge[assembly.status] || assembly.status
                            }</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1"><i class="fas fa-user text-success me-2"></i>Detalhes</h6>
                            <p class="mb-1"><strong>Criado por:</strong> ${
                              assembly.created_by_name || "N/A"
                            }</p>
                            <p class="mb-1"><strong>Data de Criação:</strong> ${new Date(
                              assembly.created_at
                            ).toLocaleDateString("pt-BR")}</p>
                            ${
                              assembly.updated_at
                                ? `<p class="mb-0"><strong>Última Atualização:</strong> ${new Date(
                                    assembly.updated_at
                                  ).toLocaleDateString("pt-BR")}</p>`
                                : ""
                            }
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-cubes text-info me-2"></i>Componentes (${
                      components.length
                    })</h6>`;

      if (components.length === 0) {
        html += `
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>Nenhum componente foi adicionado a esta montagem ainda.</div>
                </div>`;
      } else {
        html += '<div class="list-group list-group-flush">';
        components.forEach((component, index) => {
          html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="badge bg-primary rounded-pill me-3">${
                              index + 1
                            }</div>
                            <div>
                                <h6 class="mb-1">${
                                  component.component_serial
                                }</h6>
                                <small class="text-muted">${
                                  component.component_tipo_name
                                }</small>
                            </div>
                        </div>
                        <i class="fas fa-microchip text-secondary"></i>
                    </div>
                `;
        });
        html += "</div>";
      }

      html += `
                </div>
            </div>`;

      Swal.fire({
        title: `<i class="fas fa-search-plus text-info me-2"></i>Detalhes da Montagem #${assembly.id}`,
        html: html,
        width: "700px",
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
          popup: "swal-wide",
        },
      });
    }
  ).fail(function () {
    Swal.fire({
      title: "Erro!",
      text: "Erro ao carregar detalhes da montagem.",
      icon: "error",
    });
  });
}

function editAssembly(id) {
  // Carregar dados da assembly
  $.get(
    `src/controllers/CompositeController.php?action=get_assembly&id=${id}`,
    function (data) {
      const assembly = data.assembly;
      const components = data.components;

      // Definir assembly atual
      window.currentAssemblyId = id;

      // Carregar template no select
      $("#assembly_template_id").val(assembly.template_id);

      // Carregar serial se existir
      if (assembly.composite_serial) {
        $("#composite_serial").val(assembly.composite_serial);
      }

      // Mostrar requisitos do template
      showTemplateRequirements();

      // Carregar componentes existentes no array temporário
      loadExistingComponentsToTemp(components);

      // Carregar produtos disponíveis
      loadAvailableProducts();

      // Habilitar botões
      $("#saveDraftBtn, #finalizeBtn").prop("disabled", false);

      // Alterar título do modal
      $("#assemblyModal .modal-title").text(`Editando Montagem #${id}`);

      // Mostrar modal
      $("#assemblyModal").modal("show");
    }
  ).fail(function (xhr, status, error) {
    console.error(
      "Erro ao carregar assembly para edição:",
      status,
      error,
      xhr.responseText
    );
    Swal.fire({
      title: "Erro!",
      text: "Erro ao carregar dados da montagem para edição.",
      icon: "error",
    });
  });
}

function deleteAssembly(id) {
  Swal.fire({
    title: "Excluir Montagem?",
    text: "Esta ação não pode ser desfeita.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "src/controllers/CompositeController.php",
        {
          action: "delete_assembly",
          id: id,
        },
        function (response) {
          const result = JSON.parse(response);
          if (result.success) {
            Swal.fire("Sucesso!", "Montagem excluída!", "success");
            if (window.assembliesTable) window.assembliesTable.ajax.reload();
          } else {
            Swal.fire(
              "Erro!",
              result.message || "Erro ao excluir montagem.",
              "error"
            );
          }
        }
      );
    }
  });
}

// Funções para Produtos Compostos
function viewCompositeProduct(id) {
  // Implementar visualização de produto composto
  Swal.fire(
    "Info",
    "Funcionalidade de visualização em desenvolvimento.",
    "info"
  );
}

// Event listeners para modals
$("#templateModal").on("hidden.bs.modal", function () {
  $("#templateForm")[0].reset();
  $("#template_id").val("");
  $("#templateItems").empty();
  templateItemCounter = 0;
  $(".modal-title").text("Novo Template de Composição");
});

$("#assemblyModal").on("hidden.bs.modal", function () {
  // Só limpar formulário se não foi cancelado explicitamente ou se não foi fechado por sucesso
  if (!$(this).data("cancelled") && !$(this).data("success")) {
    // NUNCA deletar assembly automaticamente - apenas limpar formulário
    setTimeout(() => {
      // Limpar apenas o formulário, SEM deletar a assembly
      $("#assembly_template_id").val("");
      $("#composite_serial").val("");
      $("#templateRequirements").hide();
      $("#componentsList").empty();
      tempComponents = [];
      window.componentsToRemove = [];
      window.currentAssemblyId = null;
    }, 100);
  }
  $(this).removeData("cancelled success");
});

// Marcar como cancelado quando o botão cancelar for clicado
$("#assemblyModal").on("click", '[data-bs-dismiss="modal"]', function () {
  $("#assemblyModal").data("cancelled", true);
});

// Garantir que todas as funções sejam acessíveis globalmente
window.addTemplateItem = addTemplateItem;
window.removeTemplateItem = removeTemplateItem;
window.saveTemplate = saveTemplate;
window.editTemplate = editTemplate;
window.activateTemplate = activateTemplate;
window.deleteTemplate = deleteTemplate;
// window.loadTemplateForAssembly removida - substituída por showTemplateRequirements
window.loadAvailableProducts = loadAvailableProducts;
window.addComponentToAssembly = addComponentToAssembly;
window.loadAssemblyComponents = loadAssemblyComponents;
window.removeComponentFromAssembly = removeComponentFromAssembly;
window.finalizeAssembly = finalizeAssembly;
window.disassembleAssembly = disassembleAssembly;
window.generateQR = generateQR;
window.viewAssembly = viewAssembly;
window.editAssembly = editAssembly;
window.deleteAssembly = deleteAssembly;
window.disassembleAssembly = disassembleAssembly;
window.viewCompositeProduct = viewCompositeProduct;
window.loadInitialData = loadInitialData;
window.saveDraftAssembly = saveDraftAssembly;
window.cancelAssembly = cancelAssembly;
window.showTemplateRequirements = showTemplateRequirements;
window.createAssemblyIfNeeded = createAssemblyIfNeeded;
window.resetAssemblyModal = resetAssemblyModal;
window.removeTempComponent = removeTempComponent;
window.updateComponentsList = updateComponentsList;
window.loadExistingComponentsToTemp = loadExistingComponentsToTemp;
window.saveTempComponents = saveTempComponents;
window.removeComponentsFromAssembly = removeComponentsFromAssembly;

// Função para resetar o modal quando abrir nova montagem
function resetAssemblyModal() {
  // Resetar título
  $("#assemblyModal .modal-title").text("Nova Montagem");

  // Limpar componentes temporários e remoções
  tempComponents = [];
  window.componentsToRemove = [];

  // Limpar formulário
  $("#assembly_template_id").val("");
  $("#composite_serial").val("");
  $("#templateRequirements").hide();
  $("#componentsList").empty();

  // Desabilitar botões
  $("#saveDraftBtn")
    .prop("disabled", true)
    .html('<i class="fas fa-save me-1"></i>Salvar Progresso');
  $("#finalizeBtn")
    .prop("disabled", true)
    .html('<i class="fas fa-check me-1"></i>Finalizar Montagem');

  // Limpar assembly atual
  window.currentAssemblyId = null;
}
