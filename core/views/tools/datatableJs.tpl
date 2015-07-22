<script>
    var oTable,
        lastSelectedRow = null;

    $(document).ready(function() {
    	//document.onselectstart = function() { return false; };

        /* -- Show wizard Add button  -- */
        $(".configuration-actions").show();

        var selectedCb = [];

        <!-- Init DataTable -->
        var flagMenuCreated = false;
        $('#sideRight').sideSlide();
        oTable = $('#datatable{$object}').dataTable({

        /* Right side details */

            "rowCallback": function( row, data ) {

                /*-- Set row state color for datatable  --*/

                   var t = ["success","warning","danger","default"];
                   var url = window.location.pathname;

                   if(url =='/centreon-realtime/service' || url =='/centreon-realtime/host' ) {

                    for(var i = 0;i<t.length;i++){

                    var span = $(row).find("td:nth-child(3) span").hasClass('label-'+t[i]);


                    if (span) {
                        switch(t[i]) {
                            case 'warning' :
                            $(row).css('background-color','rgba(255,154,19,0.6)');
                            break;

                            case 'danger' :
                             $(row).css("background-color", "rgba(224,11,61,0.7)");
                            break;

                            case 'default' :
                            $(row).css('background-color','rgba(188,189,192,0.7)');
                            break;
                        }
                      }

                    }
                    }

                /* ************************************** */


                var t = data.DT_RowData.right_side_menu_list;

                if (typeof t !== 'undefined') {

                    $(row).on('click', function(e){

                        var target = $( e.target );

                        if(target.is("a")){
                            if($(this).hasClass('selected')){
                                $(this).removeClass('selected');
                            }else{
                                $(this).addClass('selected');
                            }
                            return;
                        }else{
                            if($(this).hasClass('selected')){
                                $('#tableLeft').css('margin-right','0%');
                                $('#sideRight').css('display','none');
                                //$('.Listing').css('margin-right','0px');
                            }else {
                                $('#sideRight').sideSlide.add(row,data);
                                $('#tableLeft').css('margin-right','292px');
                                $('#sideRight').css('display','block');
                                //$('.Listing').css('margin-right','15px');

                            }
                        }
                    });
                }

            },
             "infoCallback": function( settings, start, end, max, total, pre ) {
                return start +" to "+ end + " of "+ total;
              },
            "processing": true,
            "ajax": "{url_for url=$objectUrl}",
            "serverSide": true,
            "length": 25,
           // "pageLength": 10,  use this to change default page lenght
            "bStateSave": true,
            "lengthMenu": [[10, 25, 50], [10, 25, 50]],
            "language": {
                "lengthMenu": "_MENU_",
                "processing": "Loading information. Please wait a moment."
            },
            {$datatableParameters.configuration}
            'dom': "R<'row'r<'col-md-12 text-right'l T C >>t<'row'<'col-md-2'i><'col-md-10'p>>",
            responsive: true,
            "columns": [
                {$datatableParameters.header.columnHeader}
            ],
            "tableTools": {
                "sSwfPath": "{'/static/centreon/swf/dataTables/copy_csv_xls_pdf.swf'|url}",
                "aButtons": [
                    {
                        "sExtends": "collection",
                        "sButtonText": "",
                        "aButtons": [ "copy", "csv", "xls", "pdf", "print" ],
                        "sButtonClass": "btnC btnDefault"
                    }
                ]
            },
            "fnDrawCallback": function() {
                for (var ct = 0; ct < selectedCb.length; ct++) {
                    $('table[id^="datatable"] tbody tr[id=' + selectedCb[ct] + ']').toggleClass('selected');
                }
            }
        }).on( 'stateSaveParams.dt ', function (e, settings, data) {
            $.each(data.columns,function(key, d){
                d.search.search = "";
            })
        });
        
        $('#datatable{$object} tbody').on('click', 'tr', function (e){
            var active = false;
            if (!e.ctrlKey && !e.shiftKey) {
                active = $(this).hasClass('selected');
                $(this).parent().find('tr').removeClass('selected');
                if (false === active) {
            	    $(this).addClass('selected');
                }
            } else if (e.ctrlKey) {
            	$(this).toggleClass('selected');
            } else if (e.shiftKey) {
                if (lastSelectedRow === null) {
                    $(this).addClass('selected');
                } else {
                    var sort = [$(lastSelectedRow)[0].rowIndex, $(this)[0].rowIndex],
                        rows = $(this).parent().find('tr'); 
                    sort.sort(function(a, b) { return a - b; });
                    for (var i = (sort[0] - 1); i < sort[1]; i++) {
                        $(rows[i]).addClass('selected');
                    }
                }
            }
            lastSelectedRow = this;
            {if (isset($displayActionBar) && $displayActionBar === true)}
            toggleSelectedAction();
            {/if}
        });

        $('#datatable{$object} tbody ').on('click', 'a', function (e) {
            if ($(this).attr('href') && $(this).attr('href') !== '#') {
                e.stopPropagation();
            }
        });
        
        $.fn.dataTableExt.sErrMode = 'throw';
    
        $.extend($.fn.dataTableExt.oStdClasses, {
            "sSortAsc": "header headerSortDown",
            "sSortDesc": "header headerSortUp",
            "sSortable": "header"
        });


        /* -- Refresh Datatable's Datas -- */

        /*setInterval(function () {
            $(".overlay" ).qtip( "destroy", true );
            oTable.api().ajax.reload(null, false);
        }, 60000);*/

        /* -- Show datatable's selected line  -- */
        function toggleSelectedAction() {
            var countElem = $('table[id^="datatable"] tbody tr').length;
            var countChecked = $('table[id^="datatable"] tbody tr[class*="selected"]').length;
            if (countChecked == 1 && $('table[id^="datatable"] tbody tr[class*="selected"]').first().find("td:first").hasClass("dataTables_empty")) {
                countChecked = 0;
            }

            /* Display or hide listing add button */
            if ($('#selected_option').find('ul > li').length > 0) {
                if (countChecked > 0) {
                    $('#selected_option').show();
                } else {
                    $('#selected_option').hide();
                }
            }

            /* Display or hide listing addto button */
            if ( $( "#addToGroup" ).find( "ul > li" ).length > 0 ) {
                if (countChecked > 0) {
                    $( "#addToGroup" ).show();
                } else {
                    $( "#addToGroup" ).hide();
                }
            }
            
            selectedCb = [];
            $("tr.selected").each(function() {
                selectedCb.push($(this).data('id'));
            });
        }
        
        /* Add modal */
        {if isset($objectAddUrl)}
        $('#modalAdd').on('click', function(e) {
            $('#modal').removeData('bs.modal');
            $('#modal').removeData('centreonWizard');
            $('#modal .modal-content').text('');
            $('#modal').one('loaded.bs.modal', function(e) {
                $(this).centreonWizard();
            });
            $('#modal').modal({
                'remote': '{url_for url=$objectAddUrl}'
            });
        });
        {/if}

        /* Delete modal */
        {if isset($objectDeleteUrl)}
        $('#modalDelete').on('click', function(e) {
            e.preventDefault();
            $('#modal .modal-content').text('');

            /* Delete modal header */
            var $deleteHeader = $('<div></div>').addClass('modal-header');
            $('<button></button>')
                .attr('type', 'button')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('close')
                .html('&times;')
                .appendTo($deleteHeader);
            $('<h4></h4>').addClass('modal-title').text("{t}Delete{/t}").appendTo($deleteHeader);
            $deleteHeader.appendTo('#modal .modal-content');

            /* Delete modal body */
            var $deleteBody = $('<div></div>').addClass('modal-body');
            $('<span></span>').text('{t}Are you sure to delete ?{/t}').appendTo($deleteBody);
            var $listElement = $('<ul></ul>').addClass('list-unstyled');
            $('table[id^="datatable"] tbody tr[class*="selected"]').each(function(k, v) {
                $('<li></li>').html($(v).data('name')).appendTo($listElement);
            });
            $listElement.appendTo($deleteBody);
            $deleteBody.appendTo('#modal .modal-content');

            var $deleteFooter = $('<div></div>').addClass('modal-footer');
            $('<a></a>')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('btnC').addClass('btnDefault')
                .text('{t}Cancel{/t}')
                .appendTo($deleteFooter);
            var $deleteBtn = $('<button></button>')
                .attr('type', 'button')
                .addClass('btnC')
                .addClass('btnDanger')
                .text('{t}Delete{/t}')
                .appendTo($deleteFooter);
            $deleteFooter.appendTo('#modal .modal-content');
            $deleteBtn.on('click', function(e) {
                var ids = [];
                $.each($('table[id^="datatable"] tbody tr[class*="selected"]'), function(k, v) {
                    ids.push($(v).data('id'));
                });
                $.ajax({
                    url: '{url_for url=$objectDeleteUrl}',
                    type: 'POST',
                    data: {
                        'ids': ids
                    },
                    dataType: 'json',
                    success: function(data, textStatus, jqXHR) {
                        $('#modal').modal('hide');
                        alertClose();
                        if(!isJson(data)){
                            alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                            return false;
                        }
                        if (data.success) {
                            oTable.fnDraw();
                            alertMessage('{t}The objects have been successfully deleted{/t}', 'alert-success', 3);
                        } else {
                            alertMessage(data.errorMessage, 'alert-danger');
                        }
                    }
                });
            });

            $('#modal')
                .removeData('bs.modal')
                .modal();
        });
        {/if}

        /* Duplicate modal */
        {if isset($objectDuplicateUrl)}
        $('#modalDuplicate').on('click', function(e) {
            e.preventDefault();
            $('#modal').removeData('bs.modal');
            $('#modal .modal-content').text('');

            /* Duplicate modal header */
            var $duplicateHeader = $('<div></div>').addClass('modal-header');
            $('<button></button>')
                .attr('type', 'button')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('close')
                .html('&times;')
                .appendTo($duplicateHeader);
            $('<h4></h4>').addClass('modal-title').text("{t}Duplicate{/t}").appendTo($duplicateHeader);
            $duplicateHeader.appendTo('#modal .modal-content');

            /* Modal body */
            var $duplicateBody = $('<div></div>').addClass('modal-body');
            $('<span></span>').text('{t}Choose number of duplicate{/t}').appendTo($duplicateBody);
            var $form = $('<form></form>').attr('role', 'form').addClass('form-horizontal');
            $('table[id^="datatable"] tbody tr[class*="selected"]').each(function(k, v) {
                var $group = $('<div></div>').addClass('form-group');
                $('<label></label>')
                    .addClass('col-sm-4')
                    .addClass('control-label')
                    .attr('for', 'duplicate_' + $(v).data('id'))
                    .html($(v).data('name'))
                    .appendTo($group);
                $('<div></div>').addClass('col-sm-1').append(
                    $('<input></input>')
                        .attr('id', 'duplicate_' + $(v).val('id'))
                        .attr('name',  $(v).data('id'))
                        .attr('type', 'text')
                        .val(1)
                        .addClass('form-control')
                ).appendTo($group);
                $group.appendTo($form);
            });
            $form.appendTo($duplicateBody);
            $duplicateBody.appendTo('#modal .modal-content');

            var $duplicateFooter = $('<div></div>').addClass('modal-footer');
            $('<a></a>')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('btnC').addClass('btnDefault')
                .text('{t}Cancel{/t}')
                .appendTo($duplicateFooter);
            $applyBtn = $('<button></button>')
                .attr('type', 'button')
                .addClass('btnC')
                .addClass('btnPrimary')
                .text('{t}Apply{/t}')
                .appendTo($duplicateFooter);
            $applyBtn.on('click', function(e) {
                var formValues = {};
                $.each($form.serializeArray(), function(k, v) {
                    formValues[v.name] = v.value;
                });
                $.ajax({
                    url: '{url_for url=$objectDuplicateUrl}',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'duplicate': JSON.stringify(formValues)
                    },
                    success: function(data, textStatus, jqXHR) {
                        if(!isJson(data)){
                            alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                            return false;
                        }
                        $('#modal').modal('hide');
                        alertClose();
                        if (data.success) {
                            oTable.fnDraw();
                            alertMessage('{t}The objects have been successfully duplicated{/t}', 'alert-success', 3);
                        } else {
                            alertMessage(data.errorMessage, 'alert-danger');
                        }
                    }
                });
            });
            $duplicateFooter.appendTo('#modal .modal-content');

            $('#modal').modal();
        });
        {/if}


        /* Massive change modal */
        {if isset($objectMcUrl)}
                    
        $('#modalMassiveChange').on('click', function(e) {
            e.preventDefault();
            $('#modal').removeData('bs.modal');
            $('#modal .modal-content').text('');
            rules = {};
            
            /* MC modal header */
            var $mcHeader = $('<div></div>').addClass('modal-header');
            $('<button></button>')
                .attr('type', 'button')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('close')
                .html('&times;')
                .appendTo($mcHeader);
            $('<h4></h4>').addClass('modal-title').text("{t}Massive change{/t}").appendTo($mcHeader);
            $mcHeader.appendTo('#modal .modal-content');

            /* MC modal body */
            var $mcBody = $('<div></div>').addClass('modal-body');
            var $form = $('<form role="form" id="massive_change" novalidate></form>')
                    .addClass('form-horizontal');
                 
            $form.validate({
            
            });
            
            var $formGroup = $('<div></div>').addClass('form-group');
            $('<div></div>')
                .addClass('col-sm-4')
                .addClass('text-right')
                .append(
                    $('<label></label>')
                        .attr('for', 'mcChooseAttr')
                        .addClass('label-controller')
                        .text('{t}Choose the attribute to change{/t}')
                ).appendTo($formGroup);
        
        /*
        $('<div></div>')
            .addClass('flash')
            .addClass('alert')
            .addClass('fade')
            .addClass('in')
            .attr('id', 'modal-flash-message')
            .append(
                $('<button></button>')
                    .attr('type', 'button')
                    .attr('aria-hidden', 'true')
                    .addClass('close')
                    .html('&times;')
            )
            .append(
                $('<ul></ul>')
                    .attr('id', 'errors')
            )
            .appendTo($formGroup);
    */
    
            /* Get first select for choose attribute */
            var $divSelect = $('<div></div>').addClass('col-sm-6');
            var $select = $('<select></select>')
                .attr('id', 'mcChooseAttr')
                .attr('name', 'mcChooseAttr')
                .css('width', '100%')
                .append('<option></option>')
                .appendTo($divSelect);
            $divSelect.appendTo($formGroup);
            $formGroup.appendTo($form);
            $form.appendTo($mcBody);
            $mcBody.appendTo('#modal .modal-content');

            var $mcFooter = $('<div></div>').addClass('modal-footer');
            $('<a></a>')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('btnC').addClass('btnDefault')
                .text('{t}Cancel{/t}')
                .appendTo($mcFooter);
            var $applyBtn = $('<button></button>')
                .attr('type', 'button')
                .addClass('btnC')
                .addClass('btnPrimary')
                .text('{t}Apply{/t}')
                .appendTo($mcFooter);
            $mcFooter.appendTo('#modal .modal-content');

            $applyBtn.on('click', function(e) {
                var mcValues = {};
                var valueEmpty = false;
                var ids = [];
                var nb = 0;
                var field = '';
                $.each($form.serializeArray(), function(k, v) {
                    if (v['name'] != 'mcChooseAttr') {
                        if ($.trim(v['value']) != '') {
                            nb++;
                            mcValues[v['name']] = v['value'];
                        } else {
                            valueEmpty = true;
                            field += v['name']+ ", ";
                        }
                    }
                });
                
                if (valueEmpty) {
                    field = field.substring(0, (field.length) -2 );
                    alertMessage( "{t} The value(s) is empty : {/t}"+field, "alert-danger", "3");
                } else if (nb == 0) {
                    alertMessage( "{t} Please choose attribute to change {/t}", "alert-danger", "3");
                } else {
                   
                    if ($form.validate().numberOfInvalids() > 0 ) {
                        return;
                    }
   
                    $.each($('table[id^="datatable"] tbody tr[class*="selected"]'), function(k, v) {
                        ids.push($(v).data('id'));
                    });
                    
                    $.ajax({
                        url: '{url_for url=$objectMcUrl}',
                        type: 'POST',
                        data: {
                            'ids': ids,
                            'values': mcValues
                        },
                        dataType: 'json',
                        success: function(data, textStatus, jqXHR) {
                            if(!isJson(data)){
                                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                                return false;
                            }
                            $('#modal').modal('hide');
                            alertClose();
                            if (data.success) {
                                oTable.fnDraw();
                                alertMessage('{t}The changes have been applied{/t}', 'alert-success', 3);
                            } else {
                                alertMessage(data.errorMessage, 'alert-danger');
                            }
                        },
                        error : function (error){
                            alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                        }
                    });
                }
            });

            $.ajax({
                url: "{url_for url=$objectMcFieldsUrl}",
                type: "GET",
                dataType: "json",
                success: function(data, textStatus, jqXHR) {
                    if(!isJson(data)){
                        alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                        return false;
                    }
                    if(data.success){
                        $.each(data.listMc, function(k, v) {
                            $("<option></option>").val(k).text(v).appendTo("#mcChooseAttr");
                        });
                        $("#mcChooseAttr").select2({
                            allowClear: true
                        });
                        $("#mcChooseAttr").on("change", function(e) {
                            $("#mcChooseAttr").select2("val", "");
                            $("#mcChooseAttr > option[value='" + e.added.id + "']").prop({
                                disabled: true
                            });
                            $.ajax({
                                url: "{url_for url=$objectMcFieldsUrl}/" + e.added.id,
                                type: "GET",
                                dataType: "html",
                                success: function(data, textStatus, jqXHR) {
                                    $(data).appendTo($form);
                                },
                                error : function (error){
                                    alertMessage( "{t} An Error Occured {/t}", "alert-danger" )
                                }
                            });
                        });
                    }else{
                        alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                    }
                },
                error : function(error){
                    alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                }
            });

            $('#modal').modal();
        });
        {/if}

        /* Enable modal */
        {if isset($objectEnableUrl)}
        $('#modalEnable').on('click', function(e) {
            e.preventDefault();
            $('#modal .modal-content').text('');

            /* Enable modal header */
            var $EnableHeader = $('<div></div>').addClass('modal-header');
            $('<button></button>')
                .attr('type', 'button')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('close')
                .html('&times;')
                .appendTo($EnableHeader);
            $('<h4></h4>').addClass('modal-title').text("{t}Enable{/t}").appendTo($EnableHeader);
            $EnableHeader.appendTo('#modal .modal-content');

            /* Enable modal body */
            var $EnableBody = $('<div></div>').addClass('modal-body');
            $('<span></span>').text('{t}Are you sure to Enable ?{/t}').appendTo($EnableBody);
            var $listElement = $('<ul></ul>').addClass('list-unstyled');
            $('table[id^="datatable"] tbody tr[class*="selected"]').each(function(k, v) {
                $('<li></li>').html($(v).data('name')).appendTo($listElement);
            });
            $listElement.appendTo($EnableBody);
            $EnableBody.appendTo('#modal .modal-content');

            var $EnableFooter = $('<div></div>').addClass('modal-footer');
            $('<a></a>')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('btnC').addClass('btnDefault')
                .text('{t}Cancel{/t}')
                .appendTo($EnableFooter);
            var $EnableBtn = $('<button></button>')
                .attr('type', 'button')
                .addClass('btnC')
                .addClass('btnPrimary')
                .text('{t}Enable{/t}')
                .appendTo($EnableFooter);
            $EnableFooter.appendTo('#modal .modal-content');
            $EnableBtn.on('click', function(e) {
                var ids = [];
                $.each($('table[id^="datatable"] tbody tr[class*="selected"]'), function(k, v) {
                    ids.push($(v).data('id'));
                });
                $.ajax({
                    url: '{url_for url=$objectEnableUrl}',
                    type: 'POST',
                    data: {
                        'ids': ids
                    },
                    dataType: 'json',
                    success: function(data, textStatus, jqXHR) {
                        if(!isJson(data)){
                            alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                            return false;
                        }
                        $('#modal').modal('hide');
                        alertClose();
                        if (data.success) {
                            oTable.fnDraw();
                            alertMessage('{t}The objects have been successfully Enabled{/t}', 'alert-success', 3);
                        } else {
                            alertMessage(data.errorMessage, 'alert-danger');
                        }
                    }
                });
            });

            $('#modal')
                .removeData('bs.modal')
                .modal();
        });
        {/if}

        /* Disable modal */
        {if isset($objectDisableUrl)}
        $('#modalDisable').on('click', function(e) {
            e.preventDefault();
            $('#modal .modal-content').text('');

            /* Disable modal header */
            var $DisableHeader = $('<div></div>').addClass('modal-header');
            $('<button></button>')
                .attr('type', 'button')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('close')
                .html('&times;')
                .appendTo($DisableHeader);
            $('<h4></h4>').addClass('modal-title').text("{t}Disable{/t}").appendTo($DisableHeader);
            $DisableHeader.appendTo('#modal .modal-content');

            /* Disable modal body */
            var $DisableBody = $('<div></div>').addClass('modal-body');
            $('<span></span>').text('{t}Are you sure to Disable ?{/t}').appendTo($DisableBody);
            var $listElement = $('<ul></ul>').addClass('list-unstyled');
            $('table[id^="datatable"] tbody tr[class*="selected"]').each(function(k, v) {
                $('<li></li>').html($(v).data('name')).appendTo($listElement);
            });
            $listElement.appendTo($DisableBody);
            $DisableBody.appendTo('#modal .modal-content');

            var $DisableFooter = $('<div></div>').addClass('modal-footer');
            $('<a></a>')
                .attr('aria-hidden', 'true')
                .attr('data-dismiss', 'modal')
                .addClass('btnC').addClass('btnDefault')
                .text('{t}Cancel{/t}')
                .appendTo($DisableFooter);
            var $DisableBtn = $('<button></button>')
                .attr('type', 'button')
                .addClass('btnC')
                .addClass('btnDanger')
                .text('{t}Disable{/t}')
                .appendTo($DisableFooter);
            $DisableFooter.appendTo('#modal .modal-content');
            $DisableBtn.on('click', function(e) {
                var ids = [];
                $.each($('table[id^="datatable"] tbody tr[class*="selected"]'), function(k, v) {
                    ids.push($(v).data('id'));
                });
                $.ajax({
                    url: '{url_for url=$objectDisableUrl}',
                    type: 'POST',
                    data: {
                        'ids': ids
                    },
                    dataType: 'json',
                    success: function(data, textStatus, jqXHR) {
                        if(!isJson(data)){
                            alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                            return false;
                        }
                        $('#modal').modal('hide');
                        alertClose();
                        if (data.success) {
                            oTable.fnDraw();
                            alertMessage('{t}The objects have been successfully Disabled{/t}', 'alert-success', 3);
                        } else {
                            alertMessage(data.errorMessage, 'alert-danger');
                        }
                    }
                });
            });

            $('#modal')
                .removeData('bs.modal')
                .modal();
        });
        {/if}

        var requestSent = true;
        $('input.centreon-search').on('blur keyup', function(e) {
            if (e.type === 'blur' || e.keyCode == 13) {
                
                oTable.api().column($(this).data('column-index'))
                    .search(this.value)
                    .draw();
            } else {
                /* Fill the advanced search */
                var advString = $( "input[name='advsearch']" ).val();
                var searchTag = $( this ).data( "searchtag" );
                var tagRegex = new RegExp( "(^| )" + searchTag + ":((?![\"'])\\S+|\".*\"|'.*')", "g" );
                var splitRegex = new RegExp( "([^\\s\"',]+|\"([^\"]*)\"|'([^']*)')", "g" );

                /* Remove the existing values */
                advString = advString.replace( tagRegex, "").trim();
                while ( match = splitRegex.exec( $( this ).val() ) ) {
                    advString += " " + searchTag + ":" + match[1];
                }
                $( "input[name='advsearch']" ).val( advString.trim() );
            }
        });
        
        $('select.centreon-search').on('change', function(e) {
            /* Fill the advanced search */
            var advString = $( "input[name='advsearch']" ).val();
            var searchTag = $( this ).data( "searchtag" );
            var tagRegex = new RegExp( "(^| )" + searchTag + ":((?![\"'])\\S+|\".*\"|'.*')", "g" );
            var splitRegex = new RegExp( "([^\\s\"']+|\"([^\"]*)\"|'([^']*)')", "g" );
            var value = $(this).val();

            /* Remove the existing values */
            advString = advString.replace( tagRegex, "").trim();
            $( this ).find("option:selected").each(function(ind,elem){
                var value = $(elem).text();
                if (value.indexOf(' ') !== -1) {
                    value = '"' + value + '"';
                }
                advString += " " + searchTag + ":" + value;
            });
            
            $( "input[name='advsearch']" ).val( advString.trim() );
            
            if (value === null) {
                value = "";
            }
            
            oTable.api().column($(this).data('column-index'))
                .search(value)
                .draw();
        });

        $("input[name='advsearch']").centreonsearch({
            minChars: 2,
            fnRunSearch: function(obj) {
              obj.fillAssociateFields();
              $('.centreon-search').each(function(idx, element) {
                  if ($(element).data('column-index') !== undefined && $(element).val() !== null) {
                    oTable.api().column($(element).data('column-index'))
                        .search($(element).val());
                  }
              });
              oTable.api().draw();
            },
            tags: {
            {foreach $datatableParameters.header.columnSearch as $colName=>$colSearch}
                {if $colSearch['type'] == 'select'}
                    {$fieldname="select[name='$colName']"}
                {else}
                    {$fieldname="input[name='$colName']"}
                {/if}
                "{$colSearch.searchLabel}": "{$fieldname}",
            {/foreach}
            },
            associateFields: {
            {foreach $datatableParameters.header.columnSearch as $colName=>$colSearch}
                {if $colSearch['type'] == 'select'}
                    {$fieldname="select[name='$colName']"}
                {else}
                    {$fieldname="input[name='$colName']"}
                {/if}
                "{$colSearch.searchLabel}": "{$fieldname}",
            {/foreach}
            }
        });

        /* Get the list of saved search */
        $( "input[name='filters']" ).typeahead({
          minLength: 0,
          source: function(query) {
            var result = [];
            $.ajax({
              url: "{url_for url='/centreon-administration/search/list'}",
              dataType: "json",
              method: "post",
              async: false,
              data: {
                route: "{$currentRoute}",
                searchText: query
              },
              success: function( data, textStatus, jqXHR ) {
                if(!isJson(data)){
                    alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                    return false;
                }
                if ( data.success ) {
                  $.each( data.data, function( idx, value ) {
                    result.push( value['text'] );
                  });
                }else{
                    alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                }
              }
            });
            return result;
          }
        });

        /* Save search action */
        $( "#saveView" ).on( "click", function( e ) {
          alertClose();
          if ( $( "input[name='filters']" ).val().trim() === "" ) {
            alertMessage( "{t}The filters name must be set.{/t}", "alert-danger" );
            return;
          } else if ( $( "input[name='advsearch']" ).val().trim() === "" ) {
            alertMessage( "{t}The search must be set.{/t}", "alert-danger" );
            return;
          }
          $.ajax({
            url: "{url_for url='/centreon-administration/search/save'}",
            dataType: "json",
            method: "post",
            data: {
              route: "{$currentRoute}",
              label: $( "input[name='filters']" ).val().trim(),
              searchText: $( "input[name='advsearch']" ).val().trim()
            },
            success: function( data, textStatus, jqXHR ) {
              if(!isJson(data)){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                return false;
              }
              if ( data.success ) {
                alertMessage( "{t}Your search is saved.{/t}", "alert-success", 3 );
              } else {
                alertMessage( data.error, "alert-danger" );
              }
            },
            error : function(error){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
            }
          });
        });
        
        /* Bookmark search action */
        $( "#bookmarkView" ).on( "click", function( e ) {
          alertClose();
          if ( $( "input[name='filters']" ).val().trim() === "" ) {
            alertMessage( "{t}The filters name must be set.{/t}", "alert-danger" );
            return;
          } else if ( $( "input[name='advsearch']" ).val().trim() === "" ) {
            alertMessage( "{t}The search must be set.{/t}", "alert-danger" );
            return;
          }
          $.ajax({
            url: "{url_for url='/bookmark'}",
            dataType: "json",
            method: "post",
            data: {
              route: "{$currentRoute}",
              type: "search",
              label: $( "input[name='filters']" ).val().trim(),
              params: $( "input[name='filters']" ).val().trim()
            },
            success: function( data, textStatus, jqXHR ) {
              if(!isJson(data)){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                return false;
              }
              if ( data.success ) {
                loadBookmark(bookmarkUrl);
                alertMessage( "{t}Your search is bookmarked.{/t}", "alert-success", 3 );
                $( "#bookmarkStatus" ).removeClass('fa-star-o');
                $( "#bookmarkStatus" ).addClass('fa-star');

              } else {
                alertMessage( data.error, "alert-danger" );
              }
            },
            error : function (error){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
            }
          });
        });


        /* Delete search action */

        $( "#deleteView" ).on( "click", function( e ) {
          alertClose();
          if ( $( "input[name='filters']" ).val().trim() === "" ) {
            alertMessage( "{t}The filters name must be set.{/t}", "alert-danger" );
            return;
          }
          $.ajax({
            url: "{url_for url='/centreon-administration/search/delete'}",
            dataType: "json",
            method: "post",
            data: {
              route: "{$currentRoute}",
              label: $( "input[name='filters']" ).val().trim(),
            },
            success: function( data, textStatus, jqXHR ) {
              if(!isJson(data)){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                return false;
              }
              if ( data.success ) {
                alertMessage( "{t}Your search is deleted.{/t}", "alert-success", 3 );
              } else {
                alertMessage( data.error, "alert-danger" );
              }
            },
            error : function (error){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
            }
          });
        });

        /* Load search action */
        $( "#loadView" ).on( "click", function( e ) {
          alertClose();
          if ( $( "input[name='filters']" ).val().trim() === "" ) {
            alertMessage( "The filters name must be set.", "alert-danger" );
            return;
          }
          $.ajax({
            url: "{url_for url='/centreon-administration/search/load'}",
            dataType: "json",
            method: "post",
            data: {
              route: "{$currentRoute}",
              label: $( "input[name='filters']" ).val().trim(),
            },
            success: function( data, textStatus, jqXHR ) {
              if(!isJson(data)){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                return false;
              }
              if ( data.success ) {
                $( "input[name='advsearch']" ).val( data.data );
                $( "input[name='advsearch']" ).centreonsearch( "fillAssociateFields" );
                $( ".centreon-search" ).each( function( idx, element ) {
                    var value = $(element).val();
                    if (value === null) {
                        value = "";
                    }
                    oTable.api().column( $( element ).data( "column-index" ) )
                        .search( value ) ;
                });
                oTable.api().draw();
              } else {
                alertMessage( data.error, "alert-danger" );
              }
            },
            error : function (error){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
            }
          });
        });

        $("#btnSearch").on("click", function(e) {
            $("input[name='advsearch']").centreonsearch("fillAssociateFields");
            e.preventDefault();
            $('.centreon-search').each(function(idx, element) {
                if ($(element).data('column-index') !== undefined && $(element).val() !== null) {
                  oTable.api().column($(element).data('column-index'))
                    .search($(element).val());
                }
            });
            oTable.api().draw();
        });
    });
    
    
$( document ).ready(function() {
    searchFilter = getUriParametersByName('quick-access-search');
    if (searchFilter) {
        $.ajax({
            url: "{url_for url='/centreon-administration/search/load'}",
            dataType: "json",
            method: "post",
            data: {
              route: "{$currentRoute}",
              label: searchFilter,
            },
            success: function( data, textStatus, jqXHR ) {
              if(!isJson(data)){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
                return false;
              }
              if ( data.success ) {
                $( "input[name='advsearch']" ).val( data.data );
                $( "input[name='advsearch']" ).centreonsearch( "fillAssociateFields" );
                $( ".centreon-search" ).each( function( idx, element ) {
                    oTable.api().column( $( element ).data( "column-index" ) )
                        .search( $( element ).val()) ;
                });
                oTable.api().draw();
              } else {
                alertMessage( data.error, "alert-danger" );
              }
            },
            error : function (error){
                alertMessage( "{t} An Error Occured {/t}", "alert-danger" );
            }
        });
    }

     var a = $(".dataTables_length").first();
             a.css({
                 'width' : '68px'
             });
             
     $(".select2").select2();
});
</script>
