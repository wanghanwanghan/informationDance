<template>
  <div class="middle-wrapper">
    <div class="title">上传授权书</div>
    <div class="content">
      <el-upload
          class="upload-demo"
          ref="upload"
          action="https://jsonplaceholder.typicode.com/posts/"
          :on-preview="handlePreview"
          :on-remove="handleRemove"
          :file-list="fileList"
          :auto-upload="false">
        <el-button slot="trigger" size="small" type="primary">选取文件</el-button>
        <el-button style="margin-left: 10px;" size="small" type="success" @click="submitUpload">上传到服务器</el-button>
        <div slot="tip" class="el-upload__tip">只能上传jpg/png文件，且不超过1500kb</div>
      </el-upload>
    </div>
    <div class="footer"></div>
  </div>
</template>

<script>
module.exports = {
  data: function () {
    return {
      fileList: []
    }
  },
  methods: {
    submitUpload() {
      this.$refs.upload.submit();
    },
    handleRemove(file, fileList) {
      console.log(file, fileList);
    },
    handlePreview(file) {
      console.log(file);
    }
  },
  mounted() {
  }
}
</script>

<style scoped>
.middle-wrapper {
  position: absolute;
  width: 700px;
  height: 300px;
  background: white;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  margin: auto;
  border-radius: 5px;
  display: flex;
  flex-direction: column;
}

.title {
  text-align: center;
  margin: 30px auto;
  width: 300px;
  font-size: 30px;
}

.content {
  text-align: center;
  margin: 30px auto;
  width: 300px;
}

.footer {

}
</style>
