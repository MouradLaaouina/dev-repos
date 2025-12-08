import { Router } from "express";
import { postWithToken } from "../service/axiosService.js";
import { responseToDTO } from "../service/responseService.js";

const selloutRouter = Router();

// Enregistrer une vente sellout
selloutRouter.post("/sales", async (req, res) => {
  const token = req.get("DOLAPIKEY");
  if (!token) {
    return res.status(401).send({ status: "error", message: "Missing DOLAPIKEY" });
  }

  try {
    const payload = req.body || {};
    const response = await postWithToken("/selloutapi/sales", payload, token);
    return res.send({ status: "ok", result: responseToDTO(response, null) });
  } catch (error) {
    const status = error?.response?.status || 500;
    const message = error?.response?.data?.message || error?.message || "Sellout creation failed";
    console.error("Sellout create error:", message);
    return res.status(status).send({ status: "error", message });
  }
});

export default selloutRouter;
