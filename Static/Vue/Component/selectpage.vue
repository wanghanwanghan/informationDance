<template>
  <div class="body-wrapper">
    <div class="select-wrapper" v-show="selectShow">
      <div class="title-div">请选择贵企业纳税所在地</div>
      <el-select v-model="value" placeholder="请选择">
        <el-option
            v-for="item in options"
            :key="item.value"
            :label="item.label"
            :value="item.value">
        </el-option>
      </el-select>
      <el-button type="primary" @click="jump()">下一步</el-button>
    </div>
    <div v-show="myframeShow">
      <myframe></myframe>
    </div>
    <div v-show="authpageShow">
      <authpage></authpage>
    </div>
  </div>
</template>

<script>
module.exports = {
  components: {
    'myframe': 'url:./myframe.vue',
    'authpage': 'url:./authpage.vue'
  },
  data() {
    return {
      options: [
        {
          value: '其他区域',
          label: '其他区域'
        },
        {
          value: '广东（不含深圳）',
          label: '广东（不含深圳）'
        },
        {
          value: '深圳',
          label: '深圳'
        },
        {
          value: '湖南',
          label: '湖南'
        },
        {
          value: '山东（不含青岛）',
          label: '山东（不含青岛）'
        },
        {
          value: '青岛',
          label: '青岛'
        },
        {
          value: '安徽',
          label: '安徽'
        },
        {
          value: '重庆',
          label: '重庆'
        },
        {
          value: '河北',
          label: '河北'
        },
        {
          value: '四川',
          label: '四川'
        },
        {
          value: '福建（不含厦门）',
          label: '福建（不含厦门）'
        },
        {
          value: '厦门',
          label: '厦门'
        },
        {
          value: '上海',
          label: '上海'
        },
        {
          value: '浙江（不含宁波）',
          label: '浙江（不含宁波）'
        },
        {
          value: '宁波',
          label: '宁波'
        },
        {
          value: '江苏',
          label: '江苏'
        },
        {
          value: '陕西',
          label: '陕西'
        },
        {
          value: '北京',
          label: '北京'
        },
        {
          value: '河南',
          label: '河南'
        },
        {
          value: '云南',
          label: '云南'
        },
        {
          value: '辽宁',
          label: '辽宁'
        },
        {
          value: '天津',
          label: '天津'
        },
        {
          value: '湖北',
          label: '湖北'
        },
        {
          value: '江西',
          label: '江西'
        },
        {
          value: '山西',
          label: '山西'
        }
      ],
      value: null,
      selectShow: true,
      myframeShow: false,
      authpageShow: false
    }
  },
  methods: {
    jump() {
      if (this.value !== null) {
        if (this.value !== '其他区域') {
          this.selectShow = false
          this.myframeShow = true
        } else {
          this.selectShow = false
          this.authpageShow = true
        }
      }
    }
  },
  mounted() {
  }
}
</script>

<style scoped>
.body-wrapper {
  width: 100%;
  height: 100vh;
  background-image: url("../Static/bg.png");
  background-size: 100% 100%;
}

.select-wrapper {
  text-align: center;
  color: white;
}

.title-div {
  font-size: 30px;
  padding: 300px 0 50px 0;
}
</style>
