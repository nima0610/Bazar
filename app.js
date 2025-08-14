new Swiper("#swiper-1", {
    effect: "fade",
    autoplay: {
        delay: 2500,
        disableOnInteraction: false,
    },
    pagination: {
        el: "#swiper-1 .swiper-pagination",
        clickable: true,
    },
    lazy: {
        loadPrevNext: true,
    },
    loop: true
});
   
document.querySelectorAll('.grid-item').forEach(item => {
    item.addEventListener('click', () => {
        const id = item.dataset.id;
        window.location.href = `product_details.php?id=${id}`;
    });
});


