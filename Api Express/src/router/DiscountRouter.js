import { Router } from "express";
import { getWithToken, postWithToken } from "../service/axiosService.js";
import { responseToDTO } from "../service/responseService.js";

const discountRouter = Router();

/**
 * Get discount for a single product and client
 * GET /api/discounts/:productId/:clientId
 */
discountRouter.get("/:productId/:clientId", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  if (!token) {
    return res.status(401).send({ status: "error", message: "Missing DOLAPIKEY" });
  }

  const { productId, clientId } = req.params;

  try {
    const response = await getWithToken(
      `/categorydiscountapi/discount/${productId}/${clientId}`,
      token
    );
    return res.send({ status: "ok", result: responseToDTO(response, null) });
  } catch (error) {
    const status = error?.response?.status || 500;
    const message = error?.response?.data?.message || error?.message || "Failed to get discount";
    console.error("Discount fetch error:", message);
    return res.status(status).send({ status: "error", message });
  }
});

/**
 * Get discounts for multiple products (batch)
 * POST /api/discounts/batch
 * Body: { product_ids: [1, 2, 3], client_id: 123 }
 */
discountRouter.post("/batch", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  if (!token) {
    return res.status(401).send({ status: "error", message: "Missing DOLAPIKEY" });
  }

  try {
    const payload = req.body || {};
    const response = await postWithToken("/categorydiscountapi/discounts/batch", payload, token);
    return res.send({ status: "ok", result: responseToDTO(response, null) });
  } catch (error) {
    const status = error?.response?.status || 500;
    const message = error?.response?.data?.message || error?.message || "Failed to get discounts";
    console.error("Discount batch error:", message);
    return res.status(status).send({ status: "error", message });
  }
});

export default discountRouter;
