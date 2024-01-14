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
      fetch("/api/fees.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ rows: this.rows }),
      })
        .then((response) => {
          console.log(response.json());
          if (response.ok) {
            console.log("Data upload successful");
            // this.rows = [];
          } else {
            console.log("Data upload failed");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    },
  },
}).mount("#fees-import-app");
