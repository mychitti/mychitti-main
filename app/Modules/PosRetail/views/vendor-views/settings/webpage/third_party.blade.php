<style>
.coming-wrapper{
    min-height:60vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.coming-card{
    max-width:520px;
    width:100%;
    text-align:center;
    padding:40px 30px;
    border-radius:18px;
    border:1px solid #eee;
    background:#fff;
}

.coming-icon{
    width:90px;
    height:90px;
    border-radius:50%;
    background:#f4f6f9;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 20px;
    font-size:40px;
}

.coming-title{
    font-size:24px;
    font-weight:700;
    margin-bottom:10px;
}

.coming-text{
    color:#6c757d;
    font-size:14px;
    margin-bottom:25px;
}
</style>
<div class="content container-fluid">

    <div class="coming-wrapper">

        <div class="coming-card">

            <div class="coming-icon">
                🚀
            </div>

            <div class="coming-title">
                Module Coming Soon
            </div>

            <div class="coming-text">
                This feature is currently under development and will be
                available very soon for your store.
            </div>

           <a href="{{ url()->previous() }}" class="btn btn--primary">
    Okay! Go Back
</a>

        </div>

    </div>

</div>
