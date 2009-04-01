<?php include 'header.php'; ?>
<body >
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2>A Problem Occurred</h2>
            </div>
            <div id="maincontent">
                <?= $_errors['error'] ?>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
