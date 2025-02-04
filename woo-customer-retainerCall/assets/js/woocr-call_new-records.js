document.addEventListener('DOMContentLoaded', function () {
  const {
    createApp,
    nextTick
  } = Vue;
  createApp({
    data() {
      return {
        userName: '',
        userPhone: '',
        userOrders: [{
          id: '',
          name: ''
        }],
        overallStatus: 'ready',
        successMessage: '',
        errorMessage: '',
        nonce: woocrCall.nonce // Store the nonce for AJAX requests
      };
    },
    methods: {
      alertHandler(alert) {
        // Check if the alert is a string or an object
        if (typeof alert === 'string') {
          window.alert(alert);
        } else if (typeof alert === 'object' && alert.message) {
          // If it's an object, show the message property
          window.alert(alert.message);
        } else {
          // Default alert if something unexpected is passed
          window.alert('alert');
        }
      },
      submitForm_crmNewRecord() {
        const ordersArray = this.userOrders.map((order, index) => ({
          id: order.id || `temp_${index}`,
          name: order.name
        }));

        const formData = new URLSearchParams({
          action: 'add_crm_record',
          nonce: this.nonce,
          user_name: this.userName,
          user_phone: this.userPhone,
          user_orders: JSON.stringify(ordersArray),
          overall_status: 'ready',
        }).toString();

        fetch(woocrCall.ajax_url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData,
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // this.successMessage = 'Record added successfully!';
              this.alertHandler('رکورد با موفقیت اضافه شد.');
              this.resetForm();
            } else {
              // this.errorMessage = data.data.message || 'Failed to add record.';
              this.alertHandler('افزودن با خطا مواجه شد: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Fetch error:', error);
            this.errorMessage = 'An error occurred while adding the record.';
          });
      },
      addOrder() {
        this.userOrders.push({
          id: '',
          name: ''
        });
      },
      removeOrder(index) {
        this.userOrders.splice(index, 1);
      },
      resetForm() {
        this.userName = '';
        this.userPhone = '';
        this.userOrders = [{
          id: '',
          name: ''
        }];
        this.overallStatus = 'notReady';
      }
    }
  }).mount('#vue-crm-form');

  // Second Vue app for Excel handling
  createApp({
    data() {
      return {
        excelDataLoaded: false,
        selectedOptions: [],
        currFileName: '',
        currSheet: '',
        sheets: [],
        workBook: {},
        rows: [],
        columns: [],
        errorMessage: '',
        isSubmitting: false,
        processedRows: [], // To track successful rows
        failedRows: [] // To track failed rows
      };
    },
    watch: {
      // Watch the rows data for changes (indicating Excel data is loaded)
      rows(newRows) {
        if (newRows.length && !this.excelDataLoaded) {
          this.excelDataLoaded = true;
          // Automatically detect columns based on the first row
          this.rows[0].forEach((headerValue, colIndex) => {
            if (headerValue) {
              this.setDefaultSelectedOption(colIndex, headerValue);
            }
          });

          this.$nextTick(() => {
            this.initializeSelect2(); // Initialize Select2 after options are set
          });
        }
      },
    },
    methods: {
      preprocessData() {
        const phoneNumberIndex = this.selectedOptions.indexOf("phoneNumber");

        if (phoneNumberIndex === -1) {
            console.warn("No 'phoneNumber' column detected. Cannot preprocess.");
            return;
        }

        // Create a map to store merged rows by phone number
        const mergedRowsMap = {};

        this.rows.forEach((row, rowIndex) => {
            if (rowIndex === 0) return; // Skip the header row

            const phoneNumber = row[phoneNumberIndex];

            if (!phoneNumber) return; // Skip rows without a phone number

            // If this phone number already exists in the map, merge the data
            if (mergedRowsMap[phoneNumber]) {
                const existingRow = mergedRowsMap[phoneNumber];

                row.forEach((cell, colIndex) => {
                    if (!existingRow[colIndex] && cell) {
                        existingRow[colIndex] = cell; // Fill in missing data
                    }
                });
            } else {
                // If it's a new phone number, add it to the map
                mergedRowsMap[phoneNumber] = [...row];
            }
        });

        // Convert the map back to an array format and reassign it to `rows`
        this.rows = [this.rows[0], ...Object.values(mergedRowsMap)];
        console.log("Preprocessing completed. Rows merged based on phone numbers.");
    },
      async importFile(event) {
        const file = event.target.files[0];
        if (!file) return;

        try {
          const arrayBuffer = await file.arrayBuffer();
          await this.importAB(arrayBuffer, file.name);
        } catch (error) {
          console.error('Error reading file:', error);
          this.errorMessage = 'Failed to read file. Please ensure it is a valid Excel file.';
        }
      },
      async importAB(arrayBuffer, fileName) {
        try {
          const workbook = XLSX.read(arrayBuffer, {
            type: 'array'
          });
          this.currFileName = fileName;
          this.currSheet = workbook.SheetNames[0];
          this.sheets = workbook.SheetNames;
          this.workBook = workbook.Sheets;

          this.selectSheet(this.currSheet);
        } catch (error) {
          console.error('Error processing Excel file:', error);
          this.errorMessage = 'Failed to process Excel file. Please ensure the format is correct.';
        }
      },
      selectSheet(sheetName) {
        const {
          rows,
          cols
        } = this.getRowsCols(this.workBook[sheetName]);
        this.currSheet = sheetName;
        this.rows = rows;
        this.columns = cols;
      },
      getRowsCols(sheet) {
        const allRows = XLSX.utils.sheet_to_json(sheet, {
          header: 1
        });
        const columns = Array.from({
          length: XLSX.utils.decode_range(sheet['!ref']).e.c + 1
        }, (_, i) => ({
          field: String(i),
          label: XLSX.utils.encode_col(i),
        }));

        return {
          rows: allRows,
          cols: columns
        };
      },
      initializeSelect2() {
        const selects = document.querySelectorAll('.select2-dropdown');
        selects.forEach((select) => {
          if (jQuery(select).data('select2')) {
            jQuery(select).select2('destroy'); // Destroy existing Select2 instance if it exists
          }
          jQuery(select)
            .select2({
              placeholder: 'Select an option',
              allowClear: true,
              width: '135px', // Set the width of the Select2 dropdown here
            })
            .on(
              'select2:select',
              function (e) {
                const position = jQuery(this).data('position').split('.');
                const rowIndex = parseInt(position[0]);
                const colIndex = parseInt(position[1]);
                this.rows[rowIndex][colIndex] = e.target.value;
              }.bind(this)
            );
        });
      },
      setDefaultSelectedOption(colIndex, headerValue) {
        const header = headerValue.toLowerCase();
        if (header.includes('نام')) {
          this.selectedOptions[colIndex] = 'fullName';
        } else if (header.includes('شماره')) {
          this.selectedOptions[colIndex] = 'phoneNumber';
        } else if (header.includes('تاریخ وبینار') || header.includes('تاریخ عضویت')) {
          this.selectedOptions[colIndex] = 'normalMeta';
        } else if (header.includes('وبینار') || header.includes('مرجع')) {
          this.selectedOptions[colIndex] = 'source';
        } else if (header.includes('وضعیت حضور') || header.includes('وضعیت مشاهده')) {
          this.selectedOptions[colIndex] = 'importantMeta';
        } else if (header.includes('utm')) {
          this.selectedOptions[colIndex] = 'utm';
        } else {
          this.selectedOptions[colIndex] = 'normalMeta';
        }
      },
      rowsMapping() {
        console.log('Well, we defined the table Rows');

        // Get the table by ID 'batchedOrders'
        const table = document.getElementById('batchedOrders');

        if (!table) {
          console.log('Table not found!');
          return [];
        }

        // Get all rows in the table
        const rows = table.getElementsByTagName('tr');

        if (rows.length < 2) {
          console.log('Not enough rows to map data');
          return [];
        }

        const headerRow = rows[0];
        const allRowData = [];

        // Loop through each row starting from the second one
        for (let rowIndex = 1; rowIndex < rows.length; rowIndex++) {
          const currentRow = rows[rowIndex];
          const rowData = {};
          const utm = {}; // Store UTM values as UTM1, UTM2, etc.
          const normalMeta = {}; // Object to store multiple normalMeta values with their respective keys
          const importantMeta = {}; // Object to store multiple importantMeta values with their respective keys

          const headerCells = headerRow.getElementsByTagName('td');
          const dataCells = currentRow.getElementsByTagName('td');

          let utmCount = 1; // Counter for UTM fields

          for (let i = 0; i < headerCells.length; i++) {
            const selectElement = headerCells[i].querySelector('select');
            const dataValue = dataCells[i].textContent.trim();
            const headerKey = headerCells[i].querySelector('div').textContent.trim(); // Extract the text from the <div> inside <td> for key

            if (selectElement) {
              // Get the selected option from the select
              const selectedOption = selectElement.options[selectElement.selectedIndex].value;

              // Map the selected option to the corresponding data value
              if (selectedOption === 'utm') {
                utm[`UTM${utmCount}`] = dataValue; // Store UTM fields as UTM1, UTM2, UTM3, etc.
                utmCount++;
              } else if (selectedOption === 'normalMeta') {
                normalMeta[headerKey] = dataValue; // Store normalMeta values with their respective header names as keys
              } else if (selectedOption === 'importantMeta') {
                importantMeta[headerKey] = dataValue; // Store importantMeta values with their respective header names as keys
              } else {
                // For other fields, just assign them normally
                rowData[selectedOption] = dataValue;
              }
            }
          }

          // Add UTM, normalMeta, and importantMeta arrays/objects to the main object if they exist
          if (Object.keys(utm).length > 0) {
            rowData['utm'] = utm;
          }
          if (Object.keys(normalMeta).length > 0) {
            rowData['normalMeta'] = normalMeta;
          }
          if (Object.keys(importantMeta).length > 0) {
            rowData['importantMeta'] = importantMeta;
          }

          // Add the row data inside a parent object with a rowCount key
          allRowData.push({
            rowCount: rowIndex, // Label the row number
            data: rowData // Store the row's data inside the "data" key
          });
        }

        // Return the mapped data for all rows
        return allRowData;
      },

      // Method to batch submit data to the backend
      async batchSubmit(allRowData) {
        console.log('Starting batch submission');

        this.isSubmitting = true; // Trigger to disable and remove the button
        this.processedRows = [];
        this.failedRows = [];

        const batchSize = 100;
        let currentBatch = 0;

        const splitIntoBatches = (data, size) => {
          const batches = [];
          for (let i = 0; i < data.length; i += size) {
            batches.push(data.slice(i, i + size));
          }
          return batches;
        };

        const batches = splitIntoBatches(allRowData, batchSize);

        const sendBatch = () => {
          if (currentBatch < batches.length) {
            const batch = batches[currentBatch];

            // Add filename to each record in the batch
            batch.forEach(record => {
              record.data.fileName = this.currFileName;
            });

            const formData = new URLSearchParams({
              action: 'insert_crm_records',
              nonce: woocrCall.nonce,
              batch: JSON.stringify(batch)
            }).toString();

            fetch(woocrCall.ajax_url, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData,
              })
              .then(response => response.json())
              .then(responseData => {
                if (responseData.success) {
                  // Update processed rows with the correct offset for each batch
                  responseData.data.success.forEach(success => {
                    const globalIndex = success.index + currentBatch * batchSize;
                    this.processedRows.push(globalIndex);
                  });

                  responseData.data.failed.forEach(failed => {
                    const globalIndex = failed.index + currentBatch * batchSize;
                    this.failedRows.push(globalIndex);
                  });
                }

                currentBatch++; // Move to the next batch
                sendBatch(); // Recursively call sendBatch for next batch
              })
              .catch(error => {
                console.error('Batch submission error:', error);
                currentBatch++; // Move to the next batch in case of error
                sendBatch();
              });
          } else {
            this.isSubmitting = false; // Enable the button once all batches are processed
          }
        };

        sendBatch();
      },
      getRowClass(rowIndex) {
        if (this.processedRows.includes(rowIndex)) return 'success-row';
        if (this.failedRows.includes(rowIndex)) return 'failed-row';
        return '';
      },
      addOrderBatch() {
        const allRowData = this.rowsMapping();

        this.preprocessData();
        if (allRowData.length > 0) {
          this.batchSubmit(allRowData);
        } else {
          console.error('No data to submit');
        }
      },
      // Method to handle the report after all batches are submitted
      handleSubmissionReport(successRecords, failedRecords) {
        if (successRecords.length > 0) {
          console.log('Successful records:', successRecords);
          this.alertHandler(`${successRecords.length} records were added successfully!`);
        }

        if (failedRecords.length > 0) {
          console.error('Failed records:', failedRecords);
          this.alertHandler(`Submission failed for ${failedRecords.length} records.`);
          // Optionally, display detailed errors to the user or allow retrying failed records
        }
      },
      alertHandler(alert) {
        // Check if the alert is a string or an object
        if (typeof alert === 'string') {
          window.alert(alert);
        } else if (typeof alert === 'object' && alert.message) {
          // If it's an object, show the message property
          window.alert(alert.message);
        } else {
          // Default alert if something unexpected is passed
          window.alert('alert');
        }
      },
    },

    mounted() {
      if (window.jQuery) {
        console.log("jQuery is loaded and ready to use.");
      } else {
        console.error("jQuery is not available.");
      }
    },
  }).mount('#excel-insertCRM');
});