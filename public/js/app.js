$(document).ready(function () {
  // Função para limpar e resetar o formulário
  function resetForm() {
    const form = $("#mcpForm");
    form.removeClass("was-validated");
    form[0].reset();
    $("#action").val("");
    $("#id").val("");
  }

  // Manipulador para adicionar novo produto
  $("#add-mcp").on("click", function () {
    resetForm();
    $("#action").val("add");
    $(".modal-title-text").text("Adicionar Produto");
    $(".btn-acao").html('<i class="fas fa-save me-2"></i>Salvar');
    const modal = new bootstrap.Modal(document.getElementById("mcpModal"));
    modal.show();
  });

  // Manipulador para editar produto
  $(".edit-mcp").on("click", function () {
    const id = $(this).data("id");

    $.ajax({
      type: "GET",
      url: "src/controllers/ProductController.php",
      data: { id: id },
      success: function (response) {
        const mcp = JSON.parse(response);
        resetForm();

        $(".modal-title-text").text("Editar Produto");
        $(".btn-acao").html('<i class="fas fa-save me-2"></i>Atualizar');
        $("#action").val("edit");
        $("#id").val(mcp.id);
        $("#serial_number").val(mcp.serial_number);
        $("#sale_date").val(mcp.sale_date);
        $("#destination").val(mcp.destination);
        $("#warranty").val(mcp.warranty);
        $("#tipo_id").val(mcp.tipo_id);
        $("#status_id").val(mcp.status_id || 1);

        const modal = new bootstrap.Modal(document.getElementById("mcpModal"));
        modal.show();
      },
      error: function () {
        Swal.fire({
          title: "Erro!",
          text: "Houve um problema ao buscar os dados do produto.",
          icon: "error",
        });
      },
    });
  });

  // Manipulador para submissão do formulário
  $("#mcpForm").on("submit", function (event) {
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
      url: "src/controllers/ProductController.php",
      data: formData,
      success: function (response) {
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("mcpModal")
        );
        modal.hide();

        Swal.fire({
          title: "Sucesso!",
          text: isEdit
            ? "Produto atualizado com sucesso!"
            : "Produto criado com sucesso!",
          icon: "success",
        }).then(() => {
          location.reload();
        });
      },
      error: function () {
        Swal.fire({
          title: "Erro!",
          text: "Houve um problema ao salvar o produto.",
          icon: "error",
        });
      },
    });
  });

  // Manipulador para deletar produto
  $(".delete-mcp").on("click", function () {
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
          url: "src/controllers/ProductController.php",
          data: { action: "delete", id: id },
          success: function (response) {
            const res = JSON.parse(response);
            if (res.success) {
              Swal.fire({
                title: "Excluído!",
                text: "Produto foi excluído com sucesso.",
                icon: "success",
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: "Erro!",
                text: "Houve um problema ao excluir o produto.",
                icon: "error",
              });
            }
          },
          error: function () {
            Swal.fire({
              title: "Erro!",
              text: "Houve um problema ao excluir o produto.",
              icon: "error",
            });
          },
        });
      }
    });
  });

  // Manipulador para imprimir QR Code
  $(".print-qrcode").on("click", function () {
    const id = $(this).data("id");
    const qrCodePath = "public/qrcodes/" + id + ".png";

    Swal.fire({
      title: "QR Code",
      imageUrl: qrCodePath,
      imageWidth: 200,
      imageHeight: 200,
      showCloseButton: true,
      showCancelButton: true,
      confirmButtonText: '<i class="fas fa-print me-2"></i>Imprimir',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Fechar',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        const printWindow = window.open();
        printWindow.document.write(`
          <html>
            <head>
              <title>QR Code</title>
              <style>
                body {
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  height: 100vh;
                  margin: 0;
                }
                img {
                  width: 2cm;
                  height: 2cm;
                }
              </style>
            </head>
            <body>
              <img src="${qrCodePath}" />
            </body>
          </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
      }
    });
  });

  // Fechar modal ao clicar no botão de fechar
  $('.btn-close, .btn[data-bs-dismiss="modal"]').on("click", function () {
    const modal = bootstrap.Modal.getInstance(
      document.getElementById("mcpModal")
    );
    if (modal) {
      modal.hide();
    }
  });

  // Resetar formulário quando o modal for fechado
  $("#mcpModal").on("hidden.bs.modal", function () {
    resetForm();
  });
});
