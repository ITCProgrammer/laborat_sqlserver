<style>
    input::placeholder {
        font-style: italic;
        font-size: 12px;
    }

    #actionSelect button {
        position: relative;
        line-height: 0.8;
        padding: 16px 12px 8px;
        min-width: 80px;
        height: 40px;
        text-align: center;
        font-size: 14px;
        font-weight: 700;
    }

    .shortcut-label {
        position: absolute;
        top: 4px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 10px;
        font-weight: bold;
        line-height: 1;
        color: #333;
    }

    #epcTable td:last-child {
        text-align: center;
        width: 1rem;
        padding: 0.5rem;
    }

    #epcTable td,
    #epcTable th {
        text-align: center;
    }
</style>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body">

                <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                    <div id="actionSelect" style="display: flex; gap: 10px;">
                        <button class="btn btn-outline-danger action-btn" data-action="end">
                            <span class="shortcut-label">q</span>End
                        </button>
                    </div>

                    <div style="display: flex; flex-direction: column;">
                        <input type="text" id="scanInput" placeholder="Scan here..." class="form-control" style="width: 250px;">
                    </div>
                </div>

                <div id="tableContainer" style="display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                    <div id="tableWrapper" style="flex: 2;">
                        <h4 class="text-center"><strong>REPEAT DATA CORRECTION (TO END)</strong></h4>
                        <table id="tableCombined" class="table table-bordered" width="100%">
                            <thead class="bg-green">
                                <tr>
                                    <th><div align="center">No</div></th>
                                    <th><div align="center">No. Resep</div></th>
                                    <th><div align="center">Temp</div></th>
                                    <th><div align="center">Status</div></th>
                                </tr>
                            </thead>
                            <tbody id="dataBodyCombined"></tbody>
                        </table>
                    </div>

                    <div id="selectedListContainer" style="flex: 1; min-width: 250px;">
                        <h5><strong>Selected List:</strong></h5>
                        <div id="selectedList"></div>
                        <button id="submitAll" class="btn btn-success" style="margin-top: 10px;" disabled>SUBMIT</button>
                    </div>
                </div>

                <div class="modal fade modal-super-scaled" id="modalRFID" data-backdrop="static" data-keyboard="true" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog" style="width:55%">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">List of data scanned by RFID</h4>
                            </div>
                            <div class="modal-body">
                                <div class="table-scrollable" style="border: none;">
                                    <table id="epcTable" class="table table-bordered" style="width:100%; padding: 1rem">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>No Resep</th>
                                                <th>Temp</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" id="submitBtnRFID" class="btn btn-out btn-success">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require './includes/socket_helper.php' ?>
<script>
    let repeatData = [];
    let currentAction = "";
    let endList = [];
    let tableCombined = null;
    let isSocketSubscribed = false;
    let refreshHandle = null;

    function renderSelectedList() {
        const container = $("#selectedList");
        container.empty();

        const buildList = (label, list, color) => {
            if (list.length === 0) return "";

            const rows = list.map((item, index) =>
                `<li style="margin-bottom: 4px;">
                    ${index + 1}. ${item}
                    <button class="btn btn-xs btn-danger remove-btn" data-type="${label}" data-index="${index}">x</button>
                </li>`).join("");

            return `<div style="margin-top:10px;">
                <strong style="color:${color}">${label.toUpperCase()} (${list.length})</strong>
                <ul>${rows}</ul>
            </div>`;
        };

        container.append(buildList("end", endList, "green"));
        $("#submitAll").prop("disabled", endList.length === 0);
    }

    function renderCombinedTable(data) {
        const rows = (Array.isArray(data) ? data : []).map((item, idx) => [
            idx + 1,
            item.no_resep || '',
            item.product_name || '-',
            item.status || ''
        ]);

        if (!tableCombined) {
            tableCombined = $('#tableCombined').DataTable({
                data: rows,
                pageLength: 20,
                deferRender: true,
                autoWidth: false,
                lengthMenu: [[10, 20, 50, -1], [10, 20, 50, "All"]],
                columns: [
                    { title: "No", className: "text-center" },
                    { title: "No. Resep", className: "text-center" },
                    { title: "Temp", className: "text-center" },
                    { title: "Status", className: "text-center" }
                ],
                createdRow: function(row, rowData, dataIndex) {
                    const bgColor = dataIndex % 2 === 0 ? "rgb(220, 220, 220)" : "rgb(250, 235, 215)";
                    $(row).css("background-color", bgColor);
                }
            });
            return;
        }

        tableCombined.clear();
        if (rows.length > 0) {
            tableCombined.rows.add(rows);
        }
        tableCombined.draw(false);
    }

    function loadData() {
        fetch("pages/ajax/GetRepeatItems.php")
            .then(response => response.json())
            .then(data => {
                repeatData = Array.isArray(data) ? data : [];
                renderCombinedTable(repeatData);
            })
            .catch(err => {
                console.error("Gagal mengambil data:", err);
            });
    }

    function selectedItem(scanned) {
        if (scanned === "" || currentAction === "") {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Aksi Terlebih Dahulu',
                text: 'Silakan pilih tombol End, sebelum scan.'
            });
            return;
        }

        const exists = [...endList].includes(scanned);
        if (exists) {
            return;
        }

        if (currentAction === "end") endList.push(scanned);
        renderSelectedList();
    }

    $(document).ready(function() {
        let filteredRepeatData = [];
        let deletedDRData = [];

        epcTable = $('#epcTable').DataTable({
            paging: true,
            searching: true,
            info: true,
            columns: [
                { title: "No" },
                { title: "No Resep" },
                { title: "Temp" },
                { title: "Status" },
                { title: "Action", orderable: false }
            ]
        });

        socket.on('register', ({ roomId, epc, tags }) => globalProcessOnListenSocket({
            roomId,
            tags,
            epcTable,
            filteredData: filteredRepeatData,
            globalData: repeatData,
            checkFn: (item, docId) => {
                const existsOnSelected = [...endList].includes(docId.trim());
                if (existsOnSelected) {
                    addMessage(`SUCCESS_SUBSCRIBE: Already on selected ${docId}`);
                    return false;
                }
                return item.no_resep.trim() == docId.trim();
            },
            columns: [
                (row, index) => index,
                (row) => row.no_resep?.trim(),
                (row) => row.product_name || '-',
                "status",
                (row) => `<button class="btn btn-danger btn-sm remove-row" data-epc="${row.no_resep?.trim()}">x</button>`
            ]
        }));

        socket.on('dispatch', ({ roomId, epc, tags }) => globalProcessOnListenSocket({
            roomId,
            tags,
            epcTable,
            filteredData: filteredRepeatData,
            globalData: repeatData,
            checkFn: (item, docId) => {
                const existsOnSelected = [...endList].includes(docId.trim());
                if (existsOnSelected) {
                    addMessage(`SUCCESS_SUBSCRIBE: Already on selected ${docId}`);
                    return false;
                }
                return item.no_resep.trim() == docId.trim();
            },
            columns: [
                (row, index) => index,
                (row) => row.no_resep?.trim(),
                (row) => row.product_name || '-',
                "status",
                (row) => `<button class="btn btn-danger btn-sm remove-row" data-epc="${row.no_resep?.trim()}">x</button>`
            ]
        }));

        socket.on('success_subscribe', ({ roomId, epc, tags }) => globalProcessOnListenSocketForIddle({
            roomId,
            tags,
            epcTable,
            deletedDRData,
            filteredData: filteredRepeatData,
            globalData: repeatData,
            checkFn: (item, docId) => {
                const existsOnSelected = [...endList].includes(docId.trim());
                if (existsOnSelected) {
                    addMessage(`SUCCESS_SUBSCRIBE: Already on selected ${docId}`);
                    return false;
                }
                return item.no_resep.trim() == docId.trim();
            },
            columns: [
                (row, index) => index,
                (row) => row.no_resep?.trim(),
                (row) => row.product_name || '-',
                "status",
                (row) => `<button class="btn btn-danger btn-sm remove-row" data-epc="${row.no_resep?.trim()}">x</button>`
            ]
        }));

        $('#epcTable').on('click', '.remove-row', function() {
            const row = $(this).closest('tr');
            const noResep = $(this).data('epc');

            epcTable.row(row).remove().draw(false);

            if (noResep.startsWith("DR") && noResep.length > 2) {
                if (!deletedDRData.includes(noResep)) {
                    deletedDRData.push(noResep);
                }
            }

            filteredRepeatData = filteredRepeatData.filter(item => item.no_resep.trim() !== noResep);
        });

        $('#submitBtnRFID').on('click', function() {
            filteredRepeatData.map((item) => {
                selectedItem(item.no_resep.trim());
            });

            $('#modalRFID').modal('hide');
        });

        if (!isSocketSubscribed) {
            subscribe(1);
            isSocketSubscribed = true;
        }

        loadData();
        refreshHandle = setInterval(() => {
            if (!document.hidden) {
                loadData();
            }
        }, 45000);

        $(".action-btn").on("click", function() {
            $(".action-btn").removeClass("btn-primary").addClass("btn-outline-danger");
            $(this).removeClass("btn-outline-danger").addClass("btn-primary");

            currentAction = $(this).data("action");
            $("#scanInput").focus();
        });

        $("[data-action='end']").click();

        $('#scanInput').on('keypress', function(e) {
            if (e.which === 13) {
                const scanned = $(this).val().trim();
                if (scanned === "" || currentAction === "") {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih Aksi Terlebih Dahulu',
                        text: 'Silakan pilih tombol End, sebelum scan.'
                    });
                    return;
                }

                const exists = [...endList].includes(scanned);
                if (exists) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sudah Ada',
                        text: `No. Resep ${scanned} sudah dimasukkan.`
                    });
                    $(this).val("").focus();
                    return;
                }

                $.get(`pages/ajax/validate_scan_repeat.php?no_resep=${scanned}`, function(response) {
                    if (!response.valid) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data Tidak Ditemukan',
                            text: `No. Resep "${scanned}" tidak tersedia di list repeat.`
                        });
                        $('#scanInput').val('').focus();
                        return;
                    }

                    if (currentAction === "end") endList.push(scanned);
                    $('#scanInput').val("").focus();
                    renderSelectedList();
                });
            }
        });

        $('#selectedList').on('click', '.remove-btn', function() {
            const type = $(this).data("type");
            const index = $(this).data("index");

            if (type === "end") endList.splice(index, 1);
            renderSelectedList();
        });

        $('#submitAll').on('click', function() {
            const payload = {
                end: endList
            };

            Swal.fire({
                title: 'SUBMIT?',
                text: 'Pastikan data yang akan dikirim sudah benar.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'pages/ajax/submit_batch_repeat_to_end.php',
                        method: 'POST',
                        data: JSON.stringify(payload),
                        contentType: 'application/json',
                        success: function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Semua data berhasil dikirim.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            endList = [];
                            renderSelectedList();
                            loadData();
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan saat mengirim data.',
                            });
                        }
                    });
                }
            });
        });

        $(document).on('keydown', function(e) {
            const key = e.key.toLowerCase();
            if (key === 'q') {
                e.preventDefault();
                $("[data-action='end']").click();
            }
        });

        $(window).on('beforeunload', function() {
            if (refreshHandle) {
                clearInterval(refreshHandle);
                refreshHandle = null;
            }
        });
    });
</script>
