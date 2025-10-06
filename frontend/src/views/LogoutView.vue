<template>
  <div class="logout-page text-center mt-5">
    <h2>Logging out...</h2>
  </div>
</template>

<script>
export default {
  name: "LogoutView",
  async mounted() {
    try {
      await fetch("http://localhost/RoomMate/backend/pages/logout.php", {
        method: "GET",
        credentials: "include",
      });
    } catch (error) {
      console.error("Logout failed:", error);
    } finally {
      setTimeout(() => {
        this.$router.push("/login");
      }, 1000);
    }
  },
};
</script>
