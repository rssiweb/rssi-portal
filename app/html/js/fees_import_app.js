import { createApp } from "vue";

createApp({
  data() {
    return {
      file: null,
      rows: [],
      headers: [
        "date",
        "studentid",
        "fees",
        "month",
        "collectedby",
        "ptype",
        "feeyear",
      ],
      loading: false,
      error: null,
      message: null,
    };
  },
  methods: {
    parseFile() {
      Papa.parse(this.file, {
        header: true,
        skipEmptyLines: true,
        complete: (results) => {
          this.rows = results.data;
        },
      });
    },
    onFileSelected(event) {
      this.file = event.target.files[0];
      this.parseFile();
    },
    upload() {
      this.loading = true;
      fetch("/api/fees.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ rows: this.rows }),
      })
        .then(async (response) => {
          if (response.ok) {
            var res = await response.json();
            console.log(res);
            this.$refs.fileInput.value = "";
            this.message = res.message;
            this.rows = [];
            this.file = null;
          } else {
            this.error = response.error;
          }
          this.loading = false;
        })
        .catch((error) => {
          console.error("Error:", error);
          this.loading = false;
          this.error = `Error: ${error}`;
        });
    },
  },
}).mount("#fees-import-app");
