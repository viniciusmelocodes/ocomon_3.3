<script>
/* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
var obs2 = $.initialize("#table_info", function() {
    $('#table_info').html($('#table_info_hidden').html());
    $('#print-info').html($('#table_info').html());

    /* Collumn resize */
    var pressed = false;
    var start = undefined;
    var startX, startWidth;

    $("table td").mousedown(function(e) {
        start = $(this);
        pressed = true;
        startX = e.pageX;
        startWidth = $(this).width();
        $(start).addClass("resizing");
    });

    $(document).mousemove(function(e) {
        if (pressed) {
            $(start).width(startWidth + (e.pageX - startX));
        }
    });

    $(document).mouseup(function() {
        if (pressed) {
            $(start).removeClass("resizing");
            pressed = false;
        }
    });
    /* end Collumn resize */

}, {
    target: document.getElementById('modalCards') //divTicketsList divModalCards
}); /* o target limita o scopo do mutate observer */


/* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
var obs = $.initialize("#table_tickets_queue", function() {

    var criterios = $('#divCriterios').text();

    var table = $('#table_tickets_queue').DataTable({

        searching: false,
        info: false,
        paging: true,
        // pageLength: 10,
        deferRender: true,
        // fixedHeader: true,
        // scrollX: 300, /* para funcionar a coluna fixa */
        // fixedColumns: true,
        columnDefs: [{
                targets: [
                    'aberto_por',
                    'telefone',
                    'descricao',
                    'contato_email',
                    'agendado',
                    'agendado_para',
                    'data_atendimento',
                    'data_fechamento',
                    'unidade',
                    'etiqueta',
                    'prioridade',
                    'tempo_absoluto',
                    'tempo'

                ],
                visible: false,
            },
            {
                targets: ['sla', 'tempo_absoluto'],
                orderable: false,
                searchable: false,
            },
            {
                targets: [
                    'telefone',
                    'descricao',
                    'data_abertura',
                    'agendado',
                    'agendado_para',
                    'data_atendimento',
                    'data_fechamento',
                    'tempo_absoluto',
                    'tempo',
                    'sla'
                ],
                searchable: false,
            },
        ],

        colReorder: {
            iFixedColumns: 1
        },

        "language": {
            "url": "../../includes/components/datatables/datatables.pt-br.json"
        },

    });

    // new $.fn.dataTable.ColReorder(table);

    new $.fn.dataTable.Buttons(table, {

        buttons: [{
                extend: 'print',
                text: '<?= TRANS('SMART_BUTTON_PRINT', '', 1) ?>',
                title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1) ?>',
                // message: 'Relatório de Ocorrências',
                message: $('#print-info').html(),
                autoPrint: true,

                customize: function(win) {
                    $(win.document.body).find('table').addClass('display').css('font-size', '10px');
                    $(win.document.body).find('tr:nth-child(odd) td').each(function(index) {
                        $(this).css('background-color', '#f9f9f9');
                    });
                    $(win.document.body).find('h1').css('text-align', 'center');
                },
                exportOptions: {
                    columns: ':visible'
                },
            },
            {
                extend: 'copyHtml5',
                text: '<?= TRANS('SMART_BUTTON_COPY', '', 1) ?>',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excel',
                text: "Excel",
                exportOptions: {
                    columns: ':visible'
                },
                filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
            },
            {
                extend: 'csvHtml5',
                text: "CVS",
                exportOptions: {
                    columns: ':visible'
                },

                filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
            },
            {
                extend: 'pdfHtml5',
                text: "PDF",

                exportOptions: {
                    columns: ':visible',
                },
                title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                orientation: 'landscape',
                pageSize: 'A4',

                customize: function(doc) {
                    var criterios = $('#divCriterios').text()
                    var rdoc = doc;
                    var rcout = doc.content[doc.content.length - 1].table.body.length - 1;
                    doc.content.splice(0, 1);
                    var now = new Date();
                    var jsDate = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
                    doc.pageMargins = [30, 70, 30, 30];
                    doc.defaultStyle.fontSize = 8;
                    doc.styles.tableHeader.fontSize = 9;

                    doc['header'] = (function(page, pages) {
                        return {
                            table: {
                                widths: ['100%'],
                                headerRows: 0,
                                body: [
                                    [{
                                        text: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                                        alignment: 'center',
                                        fontSize: 14,
                                        bold: true,
                                        margin: [0, 10, 0, 0]
                                    }],
                                ]
                            },
                            layout: 'noBorders',
                            margin: 10
                        }
                    });

                    doc['footer'] = (function(page, pages) {
                        return {
                            columns: [{
                                    alignment: 'left',
                                    text: ['Criado em: ', {
                                        text: jsDate.toString()
                                    }]
                                },
                                {
                                    alignment: 'center',
                                    text: 'Total ' + rcout.toString() + ' linhas'
                                },
                                {
                                    alignment: 'right',
                                    text: ['página ', {
                                        text: page.toString()
                                    }, ' de ', {
                                        text: pages.toString()
                                    }]
                                }
                            ],
                            margin: 10
                        }
                    });

                    var objLayout = {};
                    objLayout['hLineWidth'] = function(i) {
                        return .8;
                    };
                    objLayout['vLineWidth'] = function(i) {
                        return .5;
                    };
                    objLayout['hLineColor'] = function(i) {
                        return '#aaa';
                    };
                    objLayout['vLineColor'] = function(i) {
                        return '#aaa';
                    };
                    objLayout['paddingLeft'] = function(i) {
                        return 5;
                    };
                    objLayout['paddingRight'] = function(i) {
                        return 35;
                    };
                    doc.content[doc.content.length - 1].layout = objLayout;

                }

            },
            {
                extend: 'colvis',
                text: '<?= TRANS('SMART_BUTTON_MANAGE_COLLUMNS', '', 1) ?>',
                // className: 'btn btn-primary',
                columns: ':gt(0)'
            },
        ]
    });

    table.buttons().container()
        .appendTo($('.display-buttons:eq(0)', table.table().container()));


}, {
    target: document.getElementById('modalCards')
}); /* o target limita o scopo do mutate observer */


</script>