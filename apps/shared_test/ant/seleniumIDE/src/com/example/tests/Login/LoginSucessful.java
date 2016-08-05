package com.example.tests;

import java.util.regex.Pattern;
import java.util.concurrent.TimeUnit;
import org.junit.*;
import static org.junit.Assert.*;
import static org.hamcrest.CoreMatchers.*;
import org.openqa.selenium.*;
import org.openqa.selenium.firefox.FirefoxDriver;
import org.openqa.selenium.support.ui.Select;

public class LoginSucessful {
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
  public void testLoginSucessful() throws Exception {
    driver.get(baseUrl + "/login");
    driver.findElement(By.name("email")).clear();
    driver.findElement(By.name("email")).sendKeys("tester+018@interest-marketing.net");
    driver.findElement(By.id("inputPassword3")).clear();
    driver.findElement(By.id("inputPassword3")).sendKeys("password");
    driver.findElement(By.xpath("//button[@type='submit']")).click();
    for (int second = 0;; second++) {
    	if (second >= 60) fail("timeout");
    	try { if ("記事一覧｜SPICE（スパイス）管理画面".equals(driver.getTitle())) break; } catch (Exception e) {}
    	Thread.sleep(1000);
    }

    assertEquals("ログアウト", driver.findElement(By.linkText("ログアウト")).getText());
    driver.findElement(By.linkText("ログアウト")).click();
    assertEquals("ログイン｜SPICE（スパイス）管理画面", driver.getTitle());
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