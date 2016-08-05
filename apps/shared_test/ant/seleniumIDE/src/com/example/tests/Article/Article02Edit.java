package com.example.tests;

import java.util.regex.Pattern;
import java.util.concurrent.TimeUnit;
import org.junit.*;
import static org.junit.Assert.*;
import static org.hamcrest.CoreMatchers.*;
import org.openqa.selenium.*;
import org.openqa.selenium.firefox.FirefoxDriver;
import org.openqa.selenium.support.ui.Select;

public class Article02Edit {
  private WebDriver driver;
  private String baseUrl;
  private boolean acceptNextAlert = true;
  private StringBuffer verificationErrors = new StringBuffer();

  @Before
  public void setUp() throws Exception {
    driver = new FirefoxDriver();
    baseUrl = "http://spice-admin.local";
    driver.manage().timeouts().implicitlyWait(30, TimeUnit.SECONDS);
  }

  @Test
  public void testArticle02Edit() throws Exception {
    driver.get(baseUrl + "/login");
    driver.findElement(By.name("email")).clear();
    driver.findElement(By.name("email")).sendKeys("tester+018@interest-marketing.net");
    driver.findElement(By.id("inputPassword3")).clear();
    driver.findElement(By.id("inputPassword3")).sendKeys("password");
    driver.findElement(By.xpath("//button[@type='submit']")).click();
    assertEquals("記事一覧", driver.findElement(By.cssSelector("h1.page-title.pull-left")).getText());
    driver.findElement(By.name("keyword")).clear();
    driver.findElement(By.name("keyword")).sendKeys("Test 12");
    driver.findElement(By.cssSelector("div.pull-right.button-group > button.btn.btn-info")).click();
    for (int second = 0;; second++) {
    	if (second >= 60) fail("timeout");
    	try { if (isElementPresent(By.linkText("編集"))) break; } catch (Exception e) {}
    	Thread.sleep(1000);
    }

    driver.findElement(By.cssSelector("a.edit-article.btn.btn-sm.btn-block.btn-primary")).click();
    assertEquals("記事の編集｜SPICE（スパイス）管理画面", driver.getTitle());
    assertEquals("記事の情報を入力してください", driver.findElement(By.cssSelector("h3.panel-title")).getText());
    assertEquals("削除", driver.findElement(By.linkText("削除")).getText());
    assertEquals("キャンセル", driver.findElement(By.linkText("キャンセル")).getText());
    assertEquals("更新", driver.findElement(By.linkText("更新")).getText());
    assertEquals("入稿", driver.findElement(By.linkText("入稿")).getText());
    assertTrue(isElementPresent(By.xpath("//form[@id='article-create']/div/div[20]/label")));
    assertEquals("HOTカウント 任意", driver.findElement(By.xpath("//form[@id='article-create']/div/div[20]/label")).getText());
    driver.findElement(By.linkText("キャンセル")).click();
    driver.findElement(By.xpath("(//button[@type='submit'])[6]")).click();
    for (int second = 0;; second++) {
    	if (second >= 60) fail("timeout");
    	try { if ("記事一覧".equals(driver.findElement(By.cssSelector("h1.page-title.pull-left")).getText())) break; } catch (Exception e) {}
    	Thread.sleep(1000);
    }

    driver.findElement(By.linkText("ログアウト")).click();
    assertEquals("ログイン", driver.findElement(By.cssSelector("h1.page-title")).getText());
  }

  @After
  public void tearDown() throws Exception {
    driver.quit();
    String verificationErrorString = verificationErrors.toString();
    if (!"".equals(verificationErrorString)) {
      fail(verificationErrorString);
    }
  }

  private boolean isElementPresent(By by) {
    try {
      driver.findElement(by);
      return true;
    } catch (NoSuchElementException e) {
      return false;
    }
  }

  private boolean isAlertPresent() {
    try {
      driver.switchTo().alert();
      return true;
    } catch (NoAlertPresentException e) {
      return false;
    }
  }

  private String closeAlertAndGetItsText() {
    try {
      Alert alert = driver.switchTo().alert();
      String alertText = alert.getText();
      if (acceptNextAlert) {
        alert.accept();
      } else {
        alert.dismiss();
      }
      return alertText;
    } finally {
      acceptNextAlert = true;
    }
  }
}
