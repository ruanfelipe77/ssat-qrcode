$(document).ready(function () {
  if (!document.getElementById("audit-table")) return;

  function formatAction(a) {
    var map = {
      create: "Criado",
      update: "Atualizado",
      delete: "Excluído",
      add_products: "+ Produtos",
      remove_products: "- Produtos",
      attach: "Vincular",
      detach: "Desvincular",
      status_change: "Mudança de Status",
      login: "Login",
      logout: "Logout",
    };
    return map[a] || a;
  }

  function formatEntity(e) {
    var map = {
      order: "Pedidos",
      product: "Produtos",
      batch: "Lotes",
      client: "Clientes",
      tipo: "Tipos",
      status: "Status",
      user: "Usuários",
    };
    return map[e] || e;
  }

  var table = $("#audit-table").DataTable({
    destroy: true,
    pagingType: "full_numbers",
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    responsive: true,
    language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json" },
    dom: "frtip",
    order: [[0, "desc"]],
    data: [],
    columns: [
      {
        data: "occurred_at",
        render: function (d) {
          if (!d) return "";
          // MySQL DATETIME comes without timezone. Interpret as UTC and convert to local
          // Example: '2025-09-22 20:10:15' -> '2025-09-22T20:10:15Z'
          var iso = String(d).replace(" ", "T") + "Z";
          var date = new Date(iso);
          if (isNaN(date.getTime())) {
            // Fallback: treat as local without Z
            date = new Date(String(d).replace(" ", "T"));
          }
          return date.toLocaleString("pt-BR");
        },
      },
      { data: "user_name", defaultContent: "-" },
      {
        data: "action",
        render: function (d) {
          return formatAction(d);
        },
      },
      {
        data: "entity_type",
        render: function (d) {
          return formatEntity(d);
        },
      },
      { data: null, render: summarizeRow },
      {
        data: null,
        orderable: false,
        className: "text-center",
        render: function (row) {
          return (
            '<button class="btn btn-sm btn-outline-primary view-audit" data-id="' +
            row.id +
            '">Ver</button>'
          );
        },
      },
    ],
  });

  function summarizeRow(row) {
    try {
      var d = row.details ? JSON.parse(row.details) : {};
      var ent = row.entity_type;
      var act = row.action;
      if (ent === "order") {
        if (act === "create") {
          var n = d.order_number || "";
          var c = d.client_id || "";
          var pc = (d.product_ids || []).length;
          return (
            "Pedido " + n + " criado (cliente " + c + ", " + pc + " produtos)"
          );
        }
        if (act === "status_change") {
          return (
            "Status " +
            (d.order_number || "") +
            ": " +
            (d.from || "-") +
            " → " +
            (d.to || "-")
          );
        }
        if (act === "add_products") {
          return (
            "Pedido " +
            (d.order_number || "") +
            ": adicionou " +
            (d.added_product_ids || []).length +
            " produto(s)"
          );
        }
        if (act === "remove_products") {
          return (
            "Pedido " +
            (d.order_number || "") +
            ": removeu " +
            (d.removed_product_ids || []).length +
            " produto(s)"
          );
        }
        if (act === "update") {
          var b = d.before || {};
          var a = d.after || {};
          return (
            "Pedido " + (a.order_number || b.order_number || "") + " atualizado"
          );
        }
        if (act === "delete") {
          return "Pedido " + (d.order_number || "") + " excluído";
        }
      }
      return act + " " + ent + " #" + (row.entity_id || "");
    } catch (e) {
      return row.action + " " + row.entity_type;
    }
  }

  function loadLogs() {
    var params = {
      q: $("#audit-q").val(),
      user_id: $("#audit-user").val(),
      action: $("#audit-action").val(),
      entity_type: $("#audit-entity").val(),
      date_from: $("#audit-df").val(),
      date_to: $("#audit-dt").val(),
      page: 1,
      page_size: 500,
    };
    $.get("src/controllers/AuditController.php", params, function (res) {
      var data = typeof res === "string" ? JSON.parse(res) : res;
      table
        .clear()
        .rows.add(data.rows || [])
        .draw();
    });
  }

  $("#audit-apply").on("click", loadLogs);
  $("#audit-clear").on("click", function () {
    $("#audit-q").val("");
    $("#audit-action").val("");
    $("#audit-entity").val("");
    $("#audit-df").val("");
    $("#audit-dt").val("");
    loadLogs();
  });

  $(document).on("click", ".view-audit", function () {
    var tr = $(this).closest("tr");
    var row = table.row(tr).data();
    var obj = row.details ? JSON.parse(row.details) : {};
    // Human-readable summary
    function humanize() {
      var ent = row.entity_type,
        act = row.action,
        d = obj || {};
      if (ent === "batch" && act === "create") {
        var qty = Array.isArray(d.products)
          ? d.products.length
          : parseInt(d.payload?.quantity || 0, 10) || 0;
        return (
          "Lote " +
          (d.batch_number || "#" + row.entity_id) +
          " criado com " +
          qty +
          " produto(s)."
        );
      }
      if (ent === "batch" && act === "delete") {
        return (
          "Lote " + (d.batch_number || "#" + row.entity_id) + " foi excluído."
        );
      }
      if (ent === "order") {
        if (act === "create")
          return "Pedido " + (d.order_number || "") + " criado.";
        if (act === "update")
          return (
            "Pedido " +
            (d.after?.order_number || d.before?.order_number || "") +
            " atualizado."
          );
        if (act === "add_products")
          return (
            "Adicionado(s) " +
            (d.added_product_ids?.length || 0) +
            " produto(s) ao pedido " +
            (d.order_number || "") +
            "."
          );
        if (act === "remove_products")
          return (
            "Removido(s) " +
            (d.removed_product_ids?.length || 0) +
            " produto(s) do pedido " +
            (d.order_number || "") +
            "."
          );
        if (act === "status_change")
          return (
            "Status do pedido " +
            (d.order_number || "") +
            " alterado de " +
            (d.from || "-") +
            " para " +
            (d.to || "-") +
            "."
          );
        if (act === "delete")
          return "Pedido " + (d.order_number || "") + " excluído.";
      }
      if (ent === "product") {
        if (act === "create")
          return "Produto #" + (row.entity_id || "") + " criado.";
        if (act === "update")
          return "Produto #" + (row.entity_id || "") + " atualizado.";
        if (act === "delete") {
          var sn = d.serial_number || d.before?.serial_number || "";
          var tipo = d.tipo_name || d.before?.tipo_name || "";
          var lote = d.batch_number || d.before?.batch_number || "";
          var parts = [];
          if (tipo) parts.push(tipo);
          if (sn) parts.push("Serial Number " + sn);
          var label = parts.length
            ? "Produto " + parts.join(" ")
            : "Produto #" + (row.entity_id || "");
          if (lote) return label + " excluído do lote " + lote + ".";
          return label + " excluído.";
        }
      }
      if (ent === "client") {
        if (act === "create") return "Cliente criado.";
        if (act === "update")
          return "Cliente #" + (row.entity_id || "") + " atualizado.";
        if (act === "delete")
          return "Cliente #" + (row.entity_id || "") + " excluído.";
      }
      if (ent === "tipo") {
        if (act === "create") return "Tipo criado.";
        if (act === "update")
          return "Tipo #" + (row.entity_id || "") + " atualizado.";
        if (act === "delete")
          return "Tipo #" + (row.entity_id || "") + " excluído.";
      }
      if (ent === "status") {
        if (act === "create") return "Status criado.";
        if (act === "update")
          return "Status #" + (row.entity_id || "") + " atualizado.";
        if (act === "delete")
          return "Status #" + (row.entity_id || "") + " excluído.";
      }
      if (ent === "user") {
        if (act === "login")
          return "Login realizado por " + (row.user_name || "usuário") + ".";
        if (act === "logout")
          return "Logout realizado por " + (row.user_name || "usuário") + ".";
        if (act === "create") return "Usuário criado.";
        if (act === "update")
          return "Usuário #" + (row.entity_id || "") + " atualizado.";
        if (act === "delete")
          return "Usuário #" + (row.entity_id || "") + " excluído.";
      }
      return (
        formatAction(act) +
        " " +
        formatEntity(ent) +
        " #" +
        (row.entity_id || "")
      );
    }

    $("#audit-human").text(humanize());

    // Extra renderer: products created in batch
    var extraHtml = "";
    function renderProductsTable(list) {
      if (!Array.isArray(list) || !list.length) {
        $("#audit-extra").hide().empty();
        return;
      }
      var html =
        '<div class="table-responsive"><table class="table table-sm table-striped">';
      html +=
        "<thead><tr><th>ID</th><th>Serial</th><th>Tipo</th><th>Garantia</th></tr></thead><tbody>";
      for (var i = 0; i < list.length; i++) {
        var p = list[i];
        html +=
          "<tr><td>" +
          (p.id ?? "") +
          "</td><td>" +
          (p.serial_number ?? "") +
          "</td><td>" +
          (p.tipo_name ?? "") +
          "</td><td>" +
          (p.warranty ?? "") +
          "</td></tr>";
      }
      html += "</tbody></table></div>";
      $("#audit-extra").html(html).show();
    }

    if (row.entity_type === "batch" && row.action === "create") {
      if (Array.isArray(obj.products) && obj.products.length) {
        renderProductsTable(obj.products);
      } else if (row.entity_id) {
        // Fetch from server if not present in details
        $("#audit-extra")
          .html(
            '<div class="text-muted small">Carregando produtos do lote...</div>'
          )
          .show();
        $.get(
          "src/controllers/BatchController.php",
          { id: row.entity_id, products: 1 },
          function (res) {
            var list = typeof res === "string" ? JSON.parse(res) : res;
            renderProductsTable(list || []);
          }
        ).fail(function () {
          $("#audit-extra").hide().empty();
        });
      } else {
        $("#audit-extra").hide().empty();
      }
    } else if (row.entity_type === 'order') {
      // Tabelas para add/remove/update em pedidos
      var sections = [];
      if (row.action === 'create') {
        if (Array.isArray(obj.products) && obj.products.length) {
          sections.push({ title: 'Produtos do pedido', list: obj.products });
        } else if (row.entity_id) {
          // Buscar lista completa caso não tenha vindo nos detalhes
          $('#audit-extra').html('<div class="text-muted small">Carregando produtos do pedido...</div>').show();
          $.get('src/controllers/ProductionOrderController.php', { id: row.entity_id, products: 1 }, function(res){
            var list = (typeof res === 'string') ? JSON.parse(res) : res;
            if (Array.isArray(list) && list.length) {
              var htmlOut = '';
              htmlOut += '<div class="mb-2 fw-semibold">Produtos do pedido</div>';
              htmlOut += '<div class="table-responsive"><table class="table table-sm table-striped">';
              htmlOut += '<thead><tr><th>ID</th><th>Serial</th><th>Tipo</th><th>Lote</th><th>Garantia</th></tr></thead><tbody>';
              for (var i=0;i<list.length;i++) {
                var p = list[i];
                htmlOut += '<tr><td>'+ (p.id ?? '') +'</td><td>'+ (p.serial_number ?? '') +'</td><td>'+ (p.tipo_name ?? '') +'</td><td>'+ (p.batch_number ?? '') +'</td><td>'+ (p.warranty ?? '') +'</td></tr>';
              }
              htmlOut += '</tbody></table></div>';
              $('#audit-extra').html(htmlOut).show();
            } else {
              $('#audit-extra').hide().empty();
            }
          }).fail(function(){ $('#audit-extra').hide().empty(); });
        }
      }
      if (row.action === 'add_products' || row.action === 'update') {
        if (Array.isArray(obj.added_products) && obj.added_products.length) {
          sections.push({ title: 'Adicionados', list: obj.added_products });
        } else if (Array.isArray(obj.added_product_ids) && obj.added_product_ids.length) {
          sections.push({ title: 'Adicionados (IDs)', list: obj.added_product_ids.map(function(id){return {id:id};}) });
        }
      }
      if (row.action === 'remove_products' || row.action === 'update') {
        if (Array.isArray(obj.removed_products) && obj.removed_products.length) {
          sections.push({ title: 'Removidos', list: obj.removed_products });
        } else if (Array.isArray(obj.removed_product_ids) && obj.removed_product_ids.length) {
          sections.push({ title: 'Removidos (IDs)', list: obj.removed_product_ids.map(function(id){return {id:id};}) });
        }
      }
      if (sections.length) {
        var htmlOut = '';
        sections.forEach(function(sec){
          htmlOut += '<div class="mb-2 fw-semibold">'+sec.title+'</div>';
          htmlOut += '<div class="table-responsive"><table class="table table-sm table-striped">';
          htmlOut += '<thead><tr><th>ID</th><th>Serial</th><th>Tipo</th><th>Lote</th><th>Garantia</th></tr></thead><tbody>';
          for (var i=0;i<sec.list.length;i++) {
            var p = sec.list[i];
            htmlOut += '<tr><td>'+ (p.id ?? '') +'</td><td>'+ (p.serial_number ?? '') +'</td><td>'+ (p.tipo_name ?? '') +'</td><td>'+ (p.batch_number ?? '') +'</td><td>'+ (p.warranty ?? '') +'</td></tr>';
          }
          htmlOut += '</tbody></table></div>';
        });
        $('#audit-extra').html(htmlOut).show();
      } else {
        $("#audit-extra").hide().empty();
      }
    } else {
      $("#audit-extra").hide().empty();
    }
    // JSON removido da UI por solicitação; mantemos apenas texto humano + tabela
    var modal = new bootstrap.Modal(
      document.getElementById("auditDetailsModal")
    );
    modal.show();
  });

  // primeira carga
  loadLogs();
});
