<template>
  <div class="logout-page text-center mt-5">
    <h2>Du loggas ut...</h2>
  </div>
</template>

<script>
export default {
  name: "LogoutView",
  async mounted() {
    try {
      await fetch("http://localhost:80/RoomMate/login/pages/logout.php", {
        method: "GET",
        credentials: "include",
      });
    } catch (error) {
      console.error("Logout failed:", error);
    } finally {
      this.$router.push("/login");
    }
  },
};
</script>
