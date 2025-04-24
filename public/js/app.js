$(document).ready(function () {
  $('#mcp-table').DataTable({
    "pagingType": "full_numbers",
    "lengthMenu": [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"]
    ],
    responsive: true,
    language: {
      "decimal": ",",
      "thousands": ".",
      "processing": "Processando...",
      "search": "Pesquisar:",
      "lengthMenu": "Mostrar _MENU_ registros",
      "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
      "infoEmpty": "Mostrando 0 até 0 de 0 registros",
      "infoFiltered": "(filtrado de _MAX_ registros no total)",
      "infoPostFix": "",
      "loadingRecords": "Carregando...",
      "zeroRecords": "Nenhum registro encontrado",
      "emptyTable": "Nenhum registro disponível na tabela",
      "paginate": {
        "first": "Primeiro",
        "previous": "Anterior",
        "next": "Próximo",
        "last": "Último"
      },
      "aria": {
        "sortAscending": ": ative para ordenar a coluna de forma ascendente",
        "sortDescending": ": ative para ordenar a coluna de forma descendente"
      }
    }
  });

  $('#add-mcp').on('click', function () {
    $('#action').val('add');
    $('#id').val('');
    $('#serial_number').val('');
    $('#sale_date').val('');
    $('#destination').val('');
    $('#warranty').val('');
    $('#mcpModal').modal('show');
  });

  $('#add-produto').on('click', function () {
    $('#action_tipo').val('add');
    $('#id').val('');
    $('#name').val('');
    $('#tipoModal').modal('show');
  });

  $('#mcpForm').on('submit', function (event) {
    event.preventDefault();
    var formData = $(this).serialize();

    $.ajax({
      type: 'POST',
      url: 'src/controllers/ProductController.php',
      data: formData,
      success: function (response) {
        Swal.fire({
          title: 'Sucesso!',
          icon: 'success',
          confirmButtonText: 'OK'
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      },
      error: function (response) {
        Swal.fire({
          title: 'Erro!',
          text: 'Houve um problema ao salvar o MCP.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    });
  });

  $('#tipoForm').on('submit', function (event) {
    event.preventDefault();
    var formData = $(this).serialize();
    $.ajax({
      type: 'POST',
      url: 'src/controllers/TipoController.php',
      data: formData,
      success: function (response) {
        Swal.fire({
          title: 'Sucesso!',
          icon: 'success',
          confirmButtonText: 'OK'
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      },
      error: function (response) {
        Swal.fire({
          title: 'Erro!',
          text: 'Houve um problema ao salvar o Tipo do Produto.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    });
  });

  $('.delete-mcp').on('click', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: 'Você tem certeza?',
      text: "Você não poderá reverter isso!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sim, delete!',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: 'POST',
          url: 'src/controllers/ProductController.php',
          data: { action: 'delete', id: id },
          success: function (response) {
            var res = JSON.parse(response);
            if (res.success) {
              Swal.fire({
                title: 'Deletado!',
                text: 'MCP foi deletado.',
                icon: 'success',
                confirmButtonText: 'OK'
              }).then((result) => {
                if (result.isConfirmed) {
                  location.reload();
                }
              });
            } else {
              Swal.fire({
                title: 'Erro!',
                text: 'Houve um problema ao deletar o MCP.',
                icon: 'error',
                confirmButtonText: 'OK'
              });
            }
          },
          error: function (response) {
            Swal.fire({
              title: 'Erro!',
              text: 'Houve um problema ao deletar o MCP.',
              icon: 'error',
              confirmButtonText: 'OK'
            });
          }
        });
      }
    });
  });

  $('.edit-mcp').on('click', function () {
    var id = $(this).data('id');
    $.ajax({
      type: 'GET',
      url: 'src/controllers/ProductController.php',
      data: { id: id },
      success: function (response) {
        var mcp = JSON.parse(response);
        $('#mcpModalLabel').text('Editar MCP');
        $('.btn-acao').text('Editar');
        $('#action').val('edit');
        $('#id').val(mcp.id);
        $('#serial_number').val(mcp.serial_number);
        $('#sale_date').val(mcp.sale_date);
        $('#destination').val(mcp.destination);
        $('#warranty').val(mcp.warranty);
        $('#tipo_id option[value="' + mcp.tipo_id + '"]').prop('selected', true);
        $('#mcpModal').modal('show');
      },
      error: function (response) {
        Swal.fire({
          title: 'Erro!',
          text: 'Houve um problema ao buscar os dados do MCP.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    });
  });

  $('.print-qrcode').on('click', function () {
    var id = $(this).data('id');
    var qrCodePath = 'public/qrcodes/' + id + '.png';

    var qrCodeImage = new Image();
    qrCodeImage.src = qrCodePath;
    qrCodeImage.onload = function () {
      var printWindow = window.open();
      printWindow.document.write('<html><body><img src="' + qrCodePath + '" style="width: 2cm; height: 2cm;" /></body></html>');
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    };
  });
});